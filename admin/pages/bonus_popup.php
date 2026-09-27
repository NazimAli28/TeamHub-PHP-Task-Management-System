<?php
// bonus_popup.php
?>

<style>
    /* Bonus Popup Styles */
    .bonus-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .bonus-popup-content {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 2px solid #f1592a;
        position: relative;
        animation: slideIn 0.5s ease;
    }

    .close-popup {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        cursor: pointer;
        color: #94a3b8;
        transition: color 0.3s ease;
    }

    .close-popup:hover {
        color: #fff;
    }

    .bonus-icon {
        font-size: 60px;
        margin-bottom: 20px;
        animation: bounce 1s infinite;
    }

    .bonus-popup-content h3 {
        color: #f1592a;
        margin-bottom: 15px;
        font-size: 24px;
    }

    .bonus-popup-content p {
        color: #cbd5e1;
        margin-bottom: 15px;
        font-size: 30px;
        font-weight: 600;
        line-height: 1.5;
    }

    .bonus-profile {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        gap: 15px;
    }

    .bonus-profile-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 3px solid #f1592a;
        object-fit: cover;
        box-shadow: 0 4px 15px #f1592a4d;
    }

    .bonus-profile-info {
        text-align: left;
    }

    .bonus-profile-name {
        font-size: 22px;
        font-weight: bold;
        color: #fff;
        margin-bottom: 5px;
    }

    .bonus-points {
        font-size: 28px;
        font-weight: bold;
        color: #f1592a;
        text-shadow: 0 2px 10px #f1592a80;
        margin: 15px 0;
        padding: 10px 20px;
        background: #f1592a1a;
        border-radius: 15px;
        border: 2px solid #f1592a4d;
    }

    .bonus-message {
        font-size: 16px;
        color: #94a3b8;
        margin-top: 10px;
        font-style: italic;
    }

    .bonus-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .bonus-btn {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .bonus-btn:not(.secondary) {
        background: linear-gradient(135deg, #f1592a, #f47b4e);
        color: white;
    }

    .bonus-btn.secondary {
        background: #334155;
        color: #cbd5e1;
    }

    .bonus-btn:hover:not(.secondary) {
        background: linear-gradient(135deg, #f1592a, #f47b4e);
        transform: translateY(-2px);
    }

    .bonus-btn.secondary:hover {
        background: #475569;
        color: white;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideIn {
        from { 
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to { 
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }

    /* Confetti Animation */
    .confetti {
        position: fixed;
        top: -10px;
        border-radius: 0;
        animation: confetti-fall 3s linear forwards;
        z-index: 9999;
    }

    @keyframes confetti-fall {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(360deg);
            opacity: 0;
        }
    }
</style>


<!-- Bonus Notification Popup -->
<audio id="popupSound" src="../../assets/sounds/bonus-popup.mp3" preload="auto"></audio>
<div id="bonusNotification" class="bonus-popup" style="display: none;">
    <div class="bonus-popup-content">
        <span class="close-popup">&times;</span>
        <div class="bonus-icon">🎉</div>
        <h3>Bonus Points Awarded!</h3>
        <div class="bonus-profile">
            <img id="bonusProfileImage" src="../../assets/images/default_profile.png" alt="Profile Picture" class="bonus-profile-image" />
            <div class="bonus-profile-info">
                <div id="bonusProfileName" class="bonus-profile-name">Designer Name</div>
                <div id="bonusPoints" class="bonus-points">0 Points</div>
            </div>
        </div>
        <p id="bonusMessage" class="bonus-message">For Good Work</p>
        <!-- <div class="bonus-actions">
            <button id="viewProgressBtn" class="bonus-btn">View Progress</button>
            <button id="closeBonusBtn" class="bonus-btn secondary">Close</button>
        </div> -->
    </div>
</div>

<script>
    const bonusPopup = document.getElementById('bonusNotification');
    function showBonusPopup(data) {
        let message = data.type === 'task'
            ? `🎯 ${data.designer} received ${data.points} bonus points for a specific task!`
            : `🌟 ${data.designer} received ${data.points} bonus points!`;

        if (data.reason && data.reason.trim() !== '') {
            message = data.reason;
        }

        // Update content using HTML structure
        document.getElementById('bonusProfileImage').src = data.picture || '../assets/images/default_profile.png';
        document.getElementById('bonusProfileName').textContent = data.designer;
        document.getElementById('bonusPoints').textContent = `${data.points} Points`;
        document.getElementById('bonusMessage').textContent = message;

        bonusPopup.style.display = 'flex';

        // Play sound effect
        const popupSound = document.getElementById('popupSound');
        if (popupSound) {
            popupSound.currentTime = 0; // Reset to start
            popupSound.play().catch(e => {
                console.log('Audio play failed:', e);
            });
        }

        // Add confetti effect for extra celebration
        createConfetti();
    }

    function hideBonusPopup() {
        bonusPopup.style.display = 'none';
    }

    function createConfetti() {
        const confettiCount = 150;
        const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', '#eb4d4b'];

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.animationDelay = Math.random() * 2 + 's';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.width = Math.random() * 10 + 5 + 'px';
            confetti.style.height = Math.random() * 10 + 5 + 'px';
            document.body.appendChild(confetti);

            // Remove confetti after animation
            setTimeout(() => {
                confetti.remove();
            }, 5000);
        }
    }

    // Event listeners for buttons
    document.getElementById('closeBonusBtn').addEventListener('click', hideBonusPopup);
    document.getElementById('viewProgressBtn').addEventListener('click', () => {
        hideBonusPopup();
        location.reload();
    });
    document.querySelector('.close-popup').addEventListener('click', hideBonusPopup);

    // Close popup when clicking outside
    bonusPopup.addEventListener('click', (e) => {
        if (e.target === bonusPopup) {
            hideBonusPopup();
        }
    });

</script>
