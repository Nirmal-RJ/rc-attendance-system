// Constants and global variables
const video = document.getElementById("video");
let actionType = "check-in";
let latitude = 0;
let longitude = 0;
let latestDetection = null;

// Initialize the application
function initializeApp() {
    addScanningEffect();
    setupEventListeners();
    addStyles();
}

// Add required styles programmatically
function addStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .container {
            position: relative;
            width: 640px;
            margin: 0 auto;
        }

        .scanner-container {
            width: 640px !important;
            height: 480px !important;
            position: relative;
            overflow: hidden;
        }

        #video {
            width: 640px !important;
            height: 480px !important;
            object-fit: cover;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }
    `;
    document.head.append(style);
}

// Setup event listeners
function setupEventListeners() {
    video.addEventListener('loadedmetadata', addScanningEffect);
    video.addEventListener("play", handleVideoPlay);
}

// Action setting function
function setAction(type) {
    cleanupDetection();
    document.getElementsByClassName("checking-button")[0].style.display = "none";
    document.getElementsByClassName("checking-button")[1].style.display = "none";

    actionType = type;
    getLocation();

     // Show the scanner container and video
     const scannerContainer = document.querySelector('.scanner-container');
     if (scannerContainer) {
         scannerContainer.style.display = 'block';
     }
    video.style.display = "block";
    startWebcam();
}

// Cleanup function
function cleanupDetection() {
    if (video.detectionInterval) {
        clearInterval(video.detectionInterval);
        video.detectionInterval = null;
    }
    
    // Remove any existing canvas
    const existingCanvas = document.querySelector('canvas');
    if (existingCanvas) {
        existingCanvas.remove();
    }
}

// Webcam handling
async function startWebcam() {
    try {
        // Cleanup any existing detection interval
        cleanupDetection();

        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri("/models"),
            faceapi.nets.faceRecognitionNet.loadFromUri("/models"),
            faceapi.nets.faceLandmark68Net.loadFromUri("/models")
        ]);

        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: 640,
                height: 480
            },
            audio: false
        });
        
        video.srcObject = stream;
        
        // Set initial video dimensions
        video.style.width = '640px';
        video.style.height = '480px';
        
        updateScanningText("Camera initialized. Starting face detection...");
    } catch (error) {
        console.error("Webcam error:", error);
        updateScanningText("Error accessing camera. Please check permissions.");
    }
}

// Face detection functions
async function getLabeledFaceDescriptions() {
    const labels = ["nita", "roopa", "hari", "khaja", "nirmal"];
    try {
        const labeledDescriptors = await Promise.all(
            labels.map(async (label) => {
                const descriptions = [];
                for (let i = 1; i <= 5; i++) {
                    try {
                        const img = await faceapi.fetchImage(`./labels/${label}/${label}${i}.jpg`);
                        const detections = await faceapi
                            .detectSingleFace(img)
                            .withFaceLandmarks()
                            .withFaceDescriptor();
                        
                        if (detections) {
                            descriptions.push(detections.descriptor);
                        }
                    } catch (error) {
                        console.warn(`Error processing ${label}${i}.jpg:`, error);
                    }
                }
                
                return descriptions.length > 0 
                    ? new faceapi.LabeledFaceDescriptors(label, descriptions)
                    : null;
            })
        );

        return labeledDescriptors.filter(descriptor => descriptor !== null);
    } catch (error) {
        console.error("Face description error:", error);
        updateScanningText("Error loading face data. Please try again.");
        throw error;
    }
}

// Video play handler
async function handleVideoPlay() {
    try {
        // Clear any existing intervals and canvas
        cleanupDetection();

        const labeledFaceDescriptors = await getLabeledFaceDescriptions();
        const faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors);

        const canvas = faceapi.createCanvasFromMedia(video);
        document.body.append(canvas);

        // Set fixed dimensions for canvas matching video size
        const displaySize = { 
            width: video.videoWidth || 640, 
            height: video.videoHeight || 480 
        };
        
        // Maintain video size
        video.style.width = `${displaySize.width}px`;
        video.style.height = `${displaySize.height}px`;
        
        // Match canvas to video size
        canvas.style.position = 'absolute';
        canvas.style.top = video.offsetTop + 'px';
        canvas.style.left = video.offsetLeft + 'px';
        canvas.width = displaySize.width;
        canvas.height = displaySize.height;

        faceapi.matchDimensions(canvas, displaySize);

        // Create a continuous detection loop
        const detectFace = async () => {
            try {
                const detections = await faceapi
                    .detectSingleFace(video)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detections) {
                    updateScanningText("No face detected. Please position your face in the frame.");
                    return; // Continue to next iteration
                }

                const resizedDetection = faceapi.resizeResults(detections, displaySize);
                const result = faceMatcher.findBestMatch(resizedDetection.descriptor);

                latestDetection = result;

                if (result.label === "unknown") {
                    promptForNameAndSubmit();
                } else {
                    captureAndSend(result.label);
                }

                // Clear the interval once a face is successfully detected
                clearInterval(detectionInterval);

            } catch (error) {
                console.error("Detection iteration error:", error);
                updateScanningText("Error during face detection. Trying again...");
            }
        };

        // Run face detection every 500ms
        const detectionInterval = setInterval(detectFace, 500);

        // Store the interval ID to clear it when needed
        video.detectionInterval = detectionInterval;

        // Initial detection attempt
        detectFace();

    } catch (error) {
        console.error("Video play error:", error);
        updateScanningText("Error during face detection. Please try again.");
    }
}

// Capture and submission functions
function promptForNameAndSubmit() {
    updateScanningText("Face scanned - Please enter your details");
    captureAndSend(null);
}

async function captureAndSend(recognizedName = null) {
    try {
        const canvasSnapshot = document.createElement("canvas");
        canvasSnapshot.width = video.videoWidth;
        canvasSnapshot.height = video.videoHeight;
        canvasSnapshot.getContext("2d").drawImage(video, 0, 0);

        const photoData = canvasSnapshot.toDataURL("image/jpeg");
        const googleMapsUrl = `https://www.google.com/maps/place/${latitude},${longitude}/@${latitude},${longitude},17z`;

        const name = recognizedName || prompt("Please enter your name:");

        // get a new date (locale machine date time)
        var date = new Date();        
        var n = date.toDateString();        
        var time = date.toLocaleTimeString();

        // log the date in the browser console
        //console.log('date:', n);
        // log the time in the browser console
        //console.log('time:',time);

        if (name) {
            const payload = {
                name: name,
                timestamp: n+" - "+time,
                location: googleMapsUrl,
                action: actionType,
                photo: photoData
            };

            localStorage.setItem("checkinData", JSON.stringify(payload));
            //console.log(JSON.stringify(payload));
            window.location.href = "../result.html";
        } else {
            // If user cancels the prompt (name is null)
            updateScanningText("Name entry cancelled. Resuming face detection...");
            handleVideoPlay();
        }
    } catch (error) {
        console.error("Capture error:", error);
        updateScanningText("Error capturing image. Please try again.");
        handleVideoPlay();
    }
}

// Location handling
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;
            },
            () => {
                console.error("Location error");
                updateScanningText("Unable to get location. Please enable location services.");
            }
        );
    } else {
        console.error("Geolocation not supported");
        updateScanningText("Geolocation is not supported by this browser.");
    }
}

// UI Effects
function addScanningEffect() {
    // Check if scanner container already exists
    let container = document.querySelector('.scanner-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'scanner-container scanning'; // Add scanning class

        const videoParent = video.parentNode;
        videoParent.insertBefore(container, video);
        container.appendChild(video);

        // Add scanning line
        const scanLine = document.createElement('div');
        scanLine.className = 'scan-line';
        container.appendChild(scanLine);

        // Add overlay
        const overlay = document.createElement('div');
        overlay.className = 'scan-overlay';
        container.appendChild(overlay);

        // Add corners
        const corners = document.createElement('div');
        corners.className = 'scanner-corners';
        corners.innerHTML = '<div></div>';
        container.appendChild(corners);
    }

    // Check if scanning text already exists
    let scanningText = document.querySelector('.scanning-text');
    if (!scanningText) {
        scanningText = document.createElement('div');
        scanningText.className = 'scanning-text';
        scanningText.textContent = 'Initializing camera...';
        container.appendChild(scanningText);
    }
}

// Update scanning text
function updateScanningText(message) {
    const scanningText = document.querySelector('.scanning-text');
    if (scanningText) {
        scanningText.textContent = message;
    } else {
        // Create the scanning text element if it doesn't exist
        const container = document.querySelector('.scanner-container');
        if (container) {
            const newScanningText = document.createElement('div');
            newScanningText.className = 'scanning-text';
            newScanningText.textContent = message;
            container.appendChild(newScanningText);
        }
    }
}

// Add cleanup on page unload
window.addEventListener('beforeunload', cleanupDetection);

// Initialize the app when the document is ready
document.addEventListener('DOMContentLoaded', initializeApp);
