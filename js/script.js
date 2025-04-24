// Constants and global variables
const video = document.getElementById("video");
const MODEL_PATH = 'models';
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

// Loading indicator functions
function showLoadingIndicator(message = "Loading face detection models...") {
    const existingIndicator = document.querySelector('.loading-indicator');
    if (existingIndicator) return;

    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-indicator';
    loadingDiv.innerHTML = `
        <div class="spinner"></div>
        <div class="loading-text">${message}</div>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoadingIndicator() {
    const loadingDiv = document.querySelector('.loading-indicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Initialize the application
async function initializeApp() {
    try {
        setupEventListeners();
        await loadModels();
        preloadImages();
    } catch (error) {
        console.error("Initialization error:", error);
        showErrorMessage("Initialization Error", 
            "Failed to initialize the application. Please refresh the page."
        );
    }
}

// Pre-load models
const loadModels = async () => {
    try {
        showLoadingIndicator();
        updateLoadingState('LOADING_MODELS');
        const startTime = Date.now();

        // Check if models are already loaded
        if (faceapi.nets.ssdMobilenetv1.isLoaded && 
            faceapi.nets.faceRecognitionNet.isLoaded && 
            faceapi.nets.faceLandmark68Net.isLoaded) {
            console.log('Models already loaded');
            hideLoadingIndicator();
            return;
        }

        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_PATH),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_PATH),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_PATH)
        ]);

        performance.modelLoadTime = Date.now() - startTime;
        console.log(`Models loaded in ${performance.modelLoadTime}ms`);
        updateLoadingState('READY');
        hideLoadingIndicator();
    } catch (error) {
        console.error("Error loading models:", error);
        hideLoadingIndicator();
        showErrorMessage("Model Loading Error", 
            "Failed to load face detection models. Please ensure:<br>" +
            "1. You have internet connection<br>" +
            "2. Model files exist in the correct directory<br>" +
            "3. Page is refreshed"
        );
        updateLoadingState('ERROR');
    }
};

// Setup event listeners
function setupEventListeners() {
    video.addEventListener('loadedmetadata', addScanningEffect);
    video.addEventListener("play", handleVideoPlay);
}

// Action setting function
async function setAction(type) {
    try {
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

        // Ensure models are loaded
        await loadModels();

        // Add scanning effect and show camera
        addScanningEffect();
        
        // Show the scanner container and video
        const scannerContainer = document.querySelector('.scanner-container');
        if (scannerContainer) {
            scannerContainer.style.display = 'block';
        }
        video.style.display = "block";

        await startWebcam();
    } catch (error) {
        console.error("Error in setAction:", error);
        showErrorMessage("Initialization Error", "Failed to start the detection system.");
    }
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
    const labels = ["nita", "roopa", "hari", "khaja", "nirmal"];
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

        const scannerContainer = document.querySelector('.scanner-container');
        const displaySize = {
            width: scannerContainer.clientWidth,
            height: scannerContainer.clientHeight
        };
        
        video.style.width = '100%';
        video.style.height = '100%';
        
        canvas.style.position = 'absolute';
        canvas.style.top = scannerContainer.offsetTop + 'px';
        canvas.style.left = scannerContainer.offsetLeft + 'px';
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
            window.location.href = "./result.html";
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
    const labels = ["nita", "roopa", "hari", "khaja", "nirmal"];
    const container = document.createElement('div');
    container.className = 'preload-images';

    container.style.position = 'absolute';
    container.style.pointerEvents = 'none';
    container.style.opacity = 0;
    
    labels.forEach(label => {
        for(let i = 1; i <= 5; i++) {
            const img = new Image();
            img.src = `./labels/${label}/${label}${i}.jpg`;
            container.appendChild(img);
        }
    });
    
    document.body.appendChild(container);
}

// Initialize the app when the document is ready
document.addEventListener('DOMContentLoaded', initializeApp);

// Add cleanup on page unload
window.addEventListener('beforeunload', cleanupDetection);
