const video = document.getElementById("video");

let actionType = "check-in";

let latitude = 0;
let longitude = 0;


function setAction(type) {
    actionType = type;
    getLocation(); // Get location on action
    video.style.display = "block";
    startWebcam(); 
}  

Promise.all([
  faceapi.nets.ssdMobilenetv1.loadFromUri("/models"),
  faceapi.nets.faceRecognitionNet.loadFromUri("/models"),
  faceapi.nets.faceLandmark68Net.loadFromUri("/models"),
]);//.then(startWebcam);

function startWebcam() 
{
  navigator.mediaDevices
    .getUserMedia({
      video: true,
      audio: false,
    })
    .then((stream) => {
      video.srcObject = stream;
    })
    .catch((error) => {
      console.error(error);
    });    
}

function getLabeledFaceDescriptions() {
  const labels = ["nita", "roopa", "hari", "khaja"];
  return Promise.all(
    labels.map(async (label) => {
      const descriptions = [];
      for (let i = 1; i <= 5; i++) {
        const img = await faceapi.fetchImage(`./labels/${label}/${label}${i}.jpg`);
        const detections = await faceapi
          .detectSingleFace(img)
          .withFaceLandmarks()
          .withFaceDescriptor();
        descriptions.push(detections.descriptor);
      }
      return new faceapi.LabeledFaceDescriptors(label, descriptions);
    })
  );
}

video.addEventListener("play", async () => {
    const labeledFaceDescriptors = await getLabeledFaceDescriptions();
    const faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors);
  
    const canvas = faceapi.createCanvasFromMedia(video);
    document.body.append(canvas);
  
    const displaySize = { width: video.width, height: video.height };
    faceapi.matchDimensions(canvas, displaySize);
  
    // Run once only
    const detections = await faceapi
      .detectSingleFace(video)
      .withFaceLandmarks()
      .withFaceDescriptor();
  
    /*if (!detections) {
      alert("No face detected. Please try again.");
      return;
    }*/
  
    const resizedDetection = faceapi.resizeResults(detections, displaySize);
    const result = faceMatcher.findBestMatch(resizedDetection.descriptor);
  
    const box = resizedDetection.detection.box;
    const drawBox = new faceapi.draw.DrawBox(box, {
      label: result.label === "unknown" ? "Face not recognized" : result.label,
    });
    drawBox.draw(canvas);
  
    if (result.label === "unknown") {
      promptForNameAndSubmit();
    } else {
      captureAndSend(result.label);
    }
  });
  
 function handleCapture() {
    if (!latestDetection) return;
  
    if (latestDetection.label === "unknown") {
      promptForNameAndSubmit();
    } else {
      captureAndSend(latestDetection.label);
    }
}

function promptForNameAndSubmit() 
{
    const name = prompt("Face not recognized. Please enter your name:");
    if (name) {
      captureAndSend(name);
    }
  }
  
  function captureAndSend(name) {
    // Capture image from video
    const canvasSnapshot = document.createElement("canvas");
    canvasSnapshot.width = video.videoWidth;
    canvasSnapshot.height = video.videoHeight;
    canvasSnapshot.getContext("2d").drawImage(video, 0, 0);
  
    const photoData = canvasSnapshot.toDataURL("image/jpeg");
  
    let googleMapsUrl = `https://www.google.com/maps/place/${latitude},${longitude}/@${latitude},${longitude},17z`;

    const payload = {
      name: name,
      timestamp: new Date().toISOString(),
      location: googleMapsUrl,
      action: actionType,
      photo: photoData,      
    };

        
  
    console.log(JSON.stringify(payload, null, 2)); // Nicely formatted alert
    localStorage.setItem("checkinData", JSON.stringify(payload)); // Save data
    window.location.href = "../result.html"; // Redirect to new page
}

function getLocation() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(success, error);
    } else { 
        console.log("Geolocation is not supported by this browser.");
    }
}

function success(position) 
{
    latitude = position.coords.latitude;
    longitude = position.coords.longitude;
}
  
function error() 
{
    alert("Sorry, no position available.");
}