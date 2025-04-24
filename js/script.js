// Constants and global variables
const video = document.getElementById("video");
let actionType = "check-in";
let latitude = 0;
let longitude = 0;
let latestDetection = null;

// Performance monitoring
const performance = {
    modelLoadTime: 0,
    detectionTime: 0,
    lastDetectionStart: 0
};

// Initialize the application
function initializeApp() {
    setupEventListeners();
    loadModels();
    addStyles();
}

// Pre-load models
const loadModels = async () => {
    try {
        updateLoadingState('LOADING_MODELS');
        const startTime = Date.now();

        await Promise.all([
            faceapi.nets.ssdMobilenetv1.load('/models'),
            faceapi.nets.faceRecognitionNet.load('/models'),
            faceapi.nets.faceLandmark68Net.load('/models')
        ]);

        performance.modelLoadTime = Date.now() - startTime;
        console.log(`Models loaded in ${performance.modelLoadTime}ms`);
        updateLoadingState('READY');
    } catch (error) {
        console.error("Error loading models:", error);
        updateLoadingState('ERROR');
    }
};

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
            display: none;
            width: 500px !important;
            height: 480px !important;
            position: relative;
            overflow: hidden;
        }

        #video {
            display: none;
            width: 500px !important;
            height: 480px !important;
            object-fit: cover;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        .preload-images {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            opacity: 0;
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
    
    // Hide the welcome message
    const welcomeMessage = document.querySelector('.welcome-message');
    if (welcomeMessage) {
        welcomeMessage.classList.add('hidden');
    }

    // Hide the buttons
    document.getElementsByClassName("checking-button")[0].style.display = "none";
    document.getElementsByClassName("checking-button")[1].style.display = "none";

    actionType = type;
    getLocation();

    // Add scanning effect and show camera
    addScanningEffect();
    
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
    
    const existingCanvas = document.querySelector('canvas');
    if (existingCanvas) {
        existingCanvas.remove();
    }
}

// Webcam handling
async function startWebcam() {
    try {
        updateLoadingState('INITIALIZING_CAMERA');
        cleanupDetection();

        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: 640,
                height: 480
            },
            audio: false
        });
        
        video.srcObject = stream;
        video.style.width = '640px';
        video.style.height = '480px';
        
        updateLoadingState('READY');
    } catch (error) {
        console.error("Webcam error:", error);
        showErrorMessage("Camera Access Error", 
            "This application requires camera access. Please ensure: <br>" +
            "1. You're using HTTPS <br>" +
            "2. Camera permissions are granted <br>" +
            "3. Your camera is properly connected"
        );
    }
}

// Face detection functions
async function getLabeledFaceDescriptions() {
    const labels = ["nita", "roopa", "hari", "khaja"];
    const maxRetries = 2;
    
    try {
        const labeledDescriptors = await Promise.all(
            labels.map(async (label) => {
                const descriptions = [];
                
                const imagePromises = Array.from({length: 5}, async (_, i) => {
                    const index = i + 1;
                    let retries = 0;
                    
                    while (retries < maxRetries) {
                        try {
                            const img = await faceapi.fetchImage(`./labels/${label}/${label}${index}.jpg`);
                            const detection = await faceapi
                                .detectSingleFace(img)
                                .withFaceLandmarks()
                                .withFaceDescriptor();
                                
                            if (detection) {
                                return detection.descriptor;
                            }
                        } catch (error) {
                            retries++;
                            console.warn(`Retry ${retries} for ${label}${index}.jpg`);
                        }
                    }
                    return null;
                });
                
                const results = await Promise.all(imagePromises);
                descriptions.push(...results.filter(desc => desc !== null));
                
                return descriptions.length > 0 
                    ? new faceapi.LabeledFaceDescriptors(label, descriptions)
                    : null;
            })
        );
        
        return labeledDescriptors.filter(desc => desc !== null);
    } catch (error) {
        console.error("Face description error:", error);
        throw error;
    }
}

// Video play handler
async function handleVideoPlay() {
    try {
        cleanupDetection();
        updateLoadingState('DETECTING_FACE');

        const labeledFaceDescriptors = await getLabeledFaceDescriptions();
        const faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors);

        const canvas = faceapi.createCanvasFromMedia(video);
        document.body.append(canvas);

        const displaySize = { 
            width: video.videoWidth || 640, 
            height: video.videoHeight || 480 
        };
        
        video.style.width = `${displaySize.width}px`;
        video.style.height = `${displaySize.height}px`;
        
        canvas.style.position = 'absolute';
        canvas.style.top = video.offsetTop + 'px';
        canvas.style.left = video.offsetLeft + 'px';
        canvas.width = displaySize.width;
        canvas.height = displaySize.height;

        faceapi.matchDimensions(canvas, displaySize);

        const detectFace = async () => {
            try {
                performance.lastDetectionStart = Date.now();

                const detections = await faceapi
                    .detectSingleFace(video)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                performance.detectionTime = Date.now() - performance.lastDetectionStart;

                if (!detections) {
                    updateLoadingState('DETECTING_FACE');
                    return;
                }

                const resizedDetection = faceapi.resizeResults(detections, displaySize);
                const result = faceMatcher.findBestMatch(resizedDetection.descriptor);

                latestDetection = result;

                if (result.label === "unknown") {
                    promptForNameAndSubmit();
                } else {
                    captureAndSend(result.label);
                }

                clearInterval(detectionInterval);

            } catch (error) {
                console.error("Detection iteration error:", error);
                updateLoadingState('ERROR');
            }
        };

        const detectionInterval = setInterval(detectFace, 500);
        video.detectionInterval = detectionInterval;
        detectFace();

    } catch (error) {
        console.error("Video play error:", error);
        updateLoadingState('ERROR');
    }
}

// Capture and submission functions
function promptForNameAndSubmit() {
    updateLoadingState('READY');
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

        if (name) {
            const payload = {
                name: name,
                timestamp: new Date().toISOString(),
                location: googleMapsUrl,
                action: actionType,
                photo: photoData
            };

            localStorage.setItem("checkinData", JSON.stringify(payload));
            window.location.href = "../result.html";
        } else {
            updateLoadingState('READY');
            handleVideoPlay();
        }
    } catch (error) {
        console.error("Capture error:", error);
        updateLoadingState('ERROR');
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
                showErrorMessage("Location Error", 
                    "Unable to get location. Please enable location services."
                );
            }
        );
    } else {
        console.error("Geolocation not supported");
        showErrorMessage("Location Error", 
            "Geolocation is not supported by this browser."
        );
    }
}

// UI Effects
function addScanningEffect() {
    let container = document.querySelector('.scanner-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'scanner-container scanning';

        const videoParent = video.parentNode;
        videoParent.insertBefore(container, video);
        container.appendChild(video);

        const scanLine = document.createElement('div');
        scanLine.className = 'scan-line';
        container.appendChild(scanLine);

        const overlay = document.createElement('div');
        overlay.className = 'scan-overlay';
        container.appendChild(overlay);

        const corners = document.createElement('div');
        corners.className = 'scanner-corners';
        corners.innerHTML = '<div></div>';
        container.appendChild(corners);
    }

    let scanningText = document.querySelector('.scanning-text');
    if (!scanningText) {
        scanningText = document.createElement('div');
        scanningText.className = 'scanning-text';
        scanningText.textContent = 'Initializing camera...';
        container.appendChild(scanningText);
    }
}

// Update loading state
function updateLoadingState(state) {
    const states = {
        LOADING_MODELS: "Loading face detection models...",
        INITIALIZING_CAMERA: "Initializing camera...",
        DETECTING_FACE: "Detecting face...",
        MATCHING_FACE: "Matching face...",
        READY: "Ready for detection",
        ERROR: "Error occurred. Please refresh."
    };
    
    updateScanningText(states[state]);
}

// Update scanning text
function updateScanningText(message) {
    const scanningText = document.querySelector('.scanning-text');
    if (scanningText) {
        scanningText.textContent = message;
    } else {
        const container = document.querySelector('.scanner-container');
        if (container) {
            const newScanningText = document.createElement('div');
            newScanningText.className = 'scanning-text';
            newScanningText.textContent = message;
            container.appendChild(newScanningText);
        }
    }
}

// Show error message
function showErrorMessage(title, message) {
    const errorMessage = document.createElement('div');
    errorMessage.className = 'error-message';
    errorMessage.innerHTML = `
        <h3>${title}</h3>
        <p>${message}</p>
    `;
    
    const container = document.querySelector('.scanner-container');
    if (container) {
        container.style.display = 'none';
        container.parentNode.insertBefore(errorMessage, container);
    }
}

// Preload images
function preloadImages() {
    const labels = ["nita", "roopa", "hari", "khaja"];
    const container = document.createElement('div');
    container.className = 'preload-images';
    
    labels.forEach(label => {
        for(let i = 1; i <= 5; i++) {
            const img = new Image();
            img.src = `./labels/${label}/${label}${i}.jpg`;
            container.appendChild(img);
        }
    });
    
    document.body.appendChild(container);
}

// Add cleanup on page unload
window.addEventListener('beforeunload', cleanupDetection);

// Initialize the app when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
    preloadImages();
});
