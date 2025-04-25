<?php
session_start();
<<<<<<<< HEAD:test.php

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login
    header("Location: ./login.html");
========
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login
    header("Location: ../login.html");
>>>>>>>> 734363640cb4171a4cae9cc370d164a0b85bcc4b:php/test.php
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Face Recognition</title>
    <script src="./js/face-api.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #0a192f, #3a5a7c);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: start;
            padding: 2rem;
            overflow: hidden;
        }

        h2 {
            font-size: 2.5rem;
            text-align: center;
            color: #ff0d00;
            margin-bottom: 2rem;
            text-shadow: 20 10 20px #ff6600;
            text-decoration: underline;
        }

        h6 {
            font-size: 1.2rem;
            margin-top: 0%;
            margin-bottom: 2rem;
            text-shadow: 0 0 20px #00d4ff;
        }

        .video-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            border: 1px solid white;
            display: block;
        }

        #video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
        }

        #scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

        .scan-line {
            position: absolute;
            left: 0;
            width: 100%;
            height: 2.5px;
            background: linear-gradient(90deg, rgba(255, 0, 0, 0.3) 0%, rgba(255, 0, 0, 1) 50%, rgba(255, 0, 0, 0.3) 100%);
            animation: scan 4s linear infinite, glow 1.5s alternate infinite;
            z-index: 11;
        }

        @keyframes scan {
            0% {
                top: 100%;
            }

            50% {
                top: 0%;
            }

            100% {
                top: 100%;
            }
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
            }

            100% {
                box-shadow: 0 0 30px rgba(255, 0, 0, 1);
            }
        }

        #output {
            margin-top: 1rem;
            font-size: 1.3em;
            color: #00d4ff;
            text-shadow: 0 0 5px #00d4ff;
            display: none;
        }

        #loading {
            margin-top: 0.5rem;
            color: #888;
            font-style: italic;
            font-size: 0.9em;
            display: none;
        }

        #video-background {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100vw;
            min-height: 100vh;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        #video-background video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            z-index: -1;
        }

        #action-panel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /*background-color: rgba(0, 0, 0, 1);*/
            background: linear-gradient(135deg, #0a192f, #3a5a7c);            
            display: flex;
            justify-content: start;
            align-items: center;
            flex-direction: column;
            z-index: 9999;
            opacity: 1;
            transition: opacity 1s ease-out;          
        }

        #action-panel.hidden {
            opacity: 0;
            pointer-events: none;
        }

        #action-panel h2 {
            margin-top: 25vh;
            font-size: 2.5rem;
            color: #ff0d00;
            margin-bottom: 2rem;
            text-shadow: 0 0 20px #ff3c00;
        }

        #action-select {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        #action-select button {
            background-color: #00d4ff;
            border: none;
            color: white;
            padding: 1em 2em;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 25px;
            cursor: pointer;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        #action-select button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        @media (min-width: 768px) {
            h2 {
                font-size: 3rem;
            }

            #output {
                font-size: 1.5em;
            }

            body {
                padding: 3rem;
            }
        }

        @media (min-width: 1024px) {
            .video-container {
                max-width: 500px;
            }

            #output {
                font-size: 1.6em;
            }
        }

        /*Result.html page styling*/
        :root {
            --bg-dark: #101c2e;
            --card-bg: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --accent: #00d4ff;
            --error: #ef4444;
            --border-radius: 15px;
            --box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .result-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            max-width: 800px;
            width: 100%;
            text-align: center;
            box-shadow: var(--box-shadow);
            animation: fadeInUp 0.6s ease;
        }

        .result-container h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d4ff, #00a2ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .result-container p {
            margin: 1rem 0;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        strong {
            display: block;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        img {
            margin: 2rem auto 1rem;
            max-width: 100%;
            border-radius: var(--border-radius);
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        img:hover {
            transform: scale(1.05);
        }

        .not-my-name-btn {
            margin-top: 2rem;
            padding: 0.8rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .not-my-name-btn:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #b91c1c, #ef4444);
        }

        .fade-out {
            animation: fadeOut 0.4s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(10px);
            }
        }

        /* Mobile (≤ 480px) */
        @media (max-width: 480px) {
            .result-container {
                padding: 1.5rem;
            }

            .result-container h1 {
                font-size: 1.8rem;
            }

            .result-container p {
                font-size: 1rem;
            }

            .not-my-name-btn {
                width: 100%;
                font-size: 0.95rem;
                padding: 0.75rem 1.5rem;
            }
        }

        /* Tablet (481px – 768px) */
        @media (min-width: 481px) and (max-width: 768px) {
            .result-container {
                padding: 2rem;
            }

            .result-container h1 {
                font-size: 2.2rem;
            }

            .result-container p {
                font-size: 1.05rem;
            }

            .not-my-name-btn {
                width: auto;
                padding: 0.75rem 2rem;
            }
        }

        /* Desktop (≥ 769px) */
        @media (min-width: 769px) {
            .result-container {
                padding: 2.5rem 3.5rem;
            }

            .result-container h1 {
                font-size: 2.5rem;
            }

            .result-container p {
                font-size: 1.1rem;
            }

            .not-my-name-btn {
                padding: 0.8rem 2rem;
            }
        }
    </style>
</head>

<body>
    <h2>Red Chariots</h2>
    <h6>Digital Attendance System</h6>

    <!-- Action Panel covering the video initially -->
    <div id="action-panel">
        <h2>Red Chariots Digital Attendance</h2>
        <h6>CHOOSE THE ACTION</h6>
        <div id="action-select">
            <button onclick="setAction('check-in')">Check In</button>
            <button onclick="setAction('check-out')">Check Out</button>
        </div>
    </div>

    <div class="video-container">
        <video id="video" autoplay muted playsinline></video>
        <div id="scanner-overlay">
            <div class="scan-line"></div>
        </div>
    </div>

    <div id="output">Initializing...</div>
    <div id="loading">Loading models, please wait...</div>

    <div id="result-container" class="result-container" style="display:none;">
        <div>
            <button id="notMyNameBtn" class="not-my-name-btn">Not my name (5)</button>
        </div>
        <h1 id="greeting"></h1>
        <p><strong>Time:</strong><span id="timestamp" style="font-family: 'Courier New', Courier, monospace;"></span>
        </p>
        <img id="photo" src="" alt="Captured Photo" />
    </div>


    <script>
        let isProcessing = false;
        let action = "";
        let latitude = 0;
        let longitude = 0;

        // Start the camera and load models as soon as the page loads
        window.onload = () => {
            start();
        };

        function setAction(type) {
            action = type;

            // Hide action panel and reveal the video
            document.getElementById('action-panel').classList.add('hidden');
            document.querySelector('.video-container').style.display = 'block';
            document.getElementById('output').style.display = 'block';
            document.getElementById('loading').style.display = 'none'; // Hide loading text once models are ready
        }

<<<<<<<< HEAD:test.php
        async function loadLabeledDescriptors() {
            const response = await fetch('./descriptors.json');
            const data = await response.json();
            return Object.entries(data).map(([label, descriptors]) => {
            const parsedDescriptors = descriptors.map(d => new Float32Array(d));
            return new faceapi.LabeledFaceDescriptors(label, parsedDescriptors);
            });
========
        async function loadLabeledImages() {
            const labels = ['nirmal', 'khaja', 'nita', 'hari', 'roopa', 'muni', 'abitha', 'veena', 'vasanth'];
            return Promise.all(
                labels.map(async label => {
                    const descriptors = [];
                    for (let i = 1; i <= 5; i++) {
                        const imgUrl = `./labels/${label}/${label}${i}.jpg`;
                        const img = await faceapi.fetchImage(imgUrl);
                        const detection = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions())
                            .withFaceLandmarks()
                            .withFaceDescriptor();
                        if (detection) {
                            descriptors.push(detection.descriptor);
                        }
                    }
                    return new faceapi.LabeledFaceDescriptors(label, descriptors);
                })
            );
>>>>>>>> 734363640cb4171a4cae9cc370d164a0b85bcc4b:php/test.php
        }

        async function captureAndRedirect(matchedLabel) {
            if (isProcessing) return;
            isProcessing = true;

            const video = document.getElementById('video');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = canvas.toDataURL('image/jpeg');
            const googleMapsUrl = `https://www.google.com/maps/place/${latitude},${longitude}/@${latitude},${longitude},17z`;

            const date = new Date();
            const n = date.toDateString();
            const time = date.toLocaleTimeString();

            const data = {
                name: matchedLabel,
                timestamp: n + " - " + time,
                location: googleMapsUrl,
                photo: imageData,
                action: action
            };

            //window.pendingCheckData = data;

            const stream = video.srcObject;
            if (stream) {
                const tracks = stream.getTracks();
                tracks.forEach(track => track.stop());
            }

            localStorage.setItem('checkinData', JSON.stringify(data));
            showResultInline(data);
        }

        function showResultInline(data) {
            document.querySelector('.video-container').style.display = 'none';
            document.getElementById('output').style.display = 'none';
            document.getElementById('result-container').style.display = 'block';
            document.getElementById('notMyNameBtn').style.display = 'inline-block';


            const greeting = document.getElementById("greeting");
            greeting.innerText = data.action === "check-in"
                ? `Welcome, ${data.name}!`
                : `Goodbye, ${data.name}!`;

            document.getElementById("timestamp").innerText = data.timestamp;
            document.getElementById("photo").src = data.photo;

            initializeNotMyNameButton(data);
        }

        function initializeNotMyNameButton(data) {
            const button = document.getElementById('notMyNameBtn');
            let countdown = 5;

            button.style.display = 'inline-block';
            button.textContent = `Not my name (${countdown})`;

            const interval = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    button.textContent = `Not my name (${countdown})`;
                } else {
                    clearInterval(interval);
                    button.classList.add('fade-out');
                    setTimeout(() => {
                        button.style.display = 'none';
                        button.classList.remove('fade-out'); // reset class
                    }, 400);
                    showToastAndRestart(data);
                }
            }, 1000);

            button.onclick = () => {
                clearInterval(interval);
                const name = prompt("Please enter your correct name:");
                if (name && name.trim()) {
                    data.name = name.trim();
                    localStorage.setItem("checkinData", JSON.stringify(data));
                    showResultInline(data); // re-render result with new name
                }
            };
        }

        function showToastAndRestart(data) 
        {
            // Send data to PHP using AJAX
            fetch('./insert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.text())
            .then(result => 
            {
                
                console.log(result); // Optional: log server response
                //alert("Data sent and toast shown!");
                // Optionally, restart or refresh data

                const toast = document.createElement("div");
                toast.innerText = data.action === "check-in"
                    ? "✅ Check-in successful!"
                    : "✅ Check-out successful!";
                Object.assign(toast.style, {
                    position: "fixed",
                    bottom: "40px",
                    left: "50%",
                    transform: "translateX(-50%)",
                    background: "#16a34a",
                    color: "white",
                    padding: "1rem 2rem",
                    borderRadius: "30px",
                    fontSize: "1rem",
                    fontWeight: "600",
                    boxShadow: "0 8px 24px rgba(0,0,0,0.2)",
                    opacity: "0",
                    transition: "opacity 0.4s ease"
                });

                document.body.appendChild(toast);
                setTimeout(() => toast.style.opacity = "1", 100);
                setTimeout(() => {
                    toast.style.opacity = "0";
                    setTimeout(() => {
                        toast.remove();
                        //location.reload();
                        action = "";
                        resetToScanningState(); // instead of reload
                    }, 500);
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
            });
            
        }

        function resetToScanningState() {
            isProcessing = false;

            // Hide result
            document.getElementById('result-container').style.display = 'none';

            // Show video + overlays
            document.getElementById('action-panel').classList.remove('hidden');
            document.querySelector('.video-container').style.display = 'block';
            document.getElementById('output').style.display = 'block';

            // Restart camera
            const video = document.getElementById('video');
            navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 320 }, height: { ideal: 240 }, facingMode: 'user' }
            }).then(stream => {
                video.srcObject = stream;




            video.onloadedmetadata = () => {
                    video.play();

                    video.addEventListener('play', () => {
                        const processVideo = async () => {
                            if (video.paused || video.ended || isProcessing && action == "") return;

                            try {
                                const detections = await faceapi
                                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                                    .withFaceLandmarks()
                                    .withFaceDescriptors();

                                const results = detections.map(d => faceMatcher.findBestMatch(d.descriptor));
                                const output = results.length
                                    ? results.map(r => r.toString()).join(', ')
                                    : "No face detected";
                                document.getElementById('output').innerText = output;

                                if (results.length > 0) {
                                    const match = results[0];
                                    if (match._label !== 'unknown' && !isProcessing && action != "") {
                                        await captureAndRedirect(match._label);
                                        return;
                                    }
                                }

                                requestAnimationFrame(processVideo);
                            } catch (error) {
                                console.error("Error processing video:", error);
                                requestAnimationFrame(processVideo);
                            }
                        };

                        requestAnimationFrame(processVideo);
                    });
                };
            }).catch(err => {
                console.error("Error restarting camera:", err);
            });
        }




        async function getLocation() {
            return new Promise((resolve) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        position => {
                            latitude = position.coords.latitude;
                            longitude = position.coords.longitude;
                            resolve();
                        },
                        error => {
                            alert("Turn on location and reload to continue:", error.message);
                            latitude = 0;
                            longitude = 0;
                        },
                        { timeout: 5000 }
                    );
                } else {
                    console.warn("Geolocation not supported.");
                    latitude = 0;
                    longitude = 0;
                }
            });
        }

        let faceMatcher;
        async function start() {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri('models');
                await faceapi.nets.faceLandmark68Net.loadFromUri('models');
                await faceapi.nets.faceRecognitionNet.loadFromUri('models');
                document.getElementById('loading').style.display = 'none';

                await getLocation();
                const labeledDescriptors = await loadLabeledDescriptors();
                faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.45);

                const video = document.getElementById('video');
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 320 }, height: { ideal: 240 }, facingMode: 'user' }
                });

                video.srcObject = stream;

                video.addEventListener('play', () => {
                    const processVideo = async () => {
                        if (video.paused || video.ended || isProcessing) return;
                        try {
                            const detections = await faceapi
                                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptors();

                            const results = detections.map(d => faceMatcher.findBestMatch(d.descriptor));
                            const output = results.length
                                ? results.map(r => r.toString()).join(', ')
                                : "No face detected";
                            document.getElementById('output').innerText = output;

                            if (results.length > 0) {
                                const match = results[0];
                                if (match._label !== 'unknown' && !isProcessing && action != "") {
                                    await captureAndRedirect(match._label);
                                    return;
                                }
                            }
                            requestAnimationFrame(processVideo);
                        } catch (error) {
                            console.error("Error processing video:", error);
                            requestAnimationFrame(processVideo);
                        }
                    };
                    processVideo();
                });

            } catch (error) {
                console.error("Error in face recognition:", error);
                document.getElementById('output').innerText = "Error loading face recognition";
            }
        }
    </script>
</body>

</html>