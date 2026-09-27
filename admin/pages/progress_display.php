<?php
// progress_display.php
session_start();
include '../../config/db.php';
date_default_timezone_set('Asia/Karachi');

// Check if bonus points were recently added to show popup
$bonusData = null;
if (isset($_SESSION['bonus_added'])) {
    $bonusData = $_SESSION['bonus_added'];
    unset($_SESSION['bonus_added']); // Clear the session variable after use
}

function getEmployeeProgress($type)
{
    global $conn;

    if ($type === 'daily') {
        $where = "DATE(assigned_time) = CURDATE()";
    } else {
        $where = "MONTH(assigned_time) = MONTH(CURDATE()) AND YEAR(assigned_time) = YEAR(CURDATE())";
    }

    $query = "
        SELECT designer_name, SUM(allotted_time) AS total_allotted, SUM(points_gained) + SUM(bonus_points) AS total_gained, u.profile_picture
        FROM designer_points dp
        JOIN users u ON dp.designer_name = u.name
        WHERE $where
        GROUP BY designer_name
        ORDER BY total_gained DESC
    ";

    $result = $conn->query($query);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'name' => $row['designer_name'],
            'allotted' => round($row['total_allotted'] / 10, 2),
            'gained' => round($row['total_gained'], 2),
            'picture' => $row['profile_picture'] ?: '../assets/images/default_profile.png',
        ];
    }

    return $data;
}

$dailyData = getEmployeeProgress('daily');
$monthlyData = getEmployeeProgress('monthly');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png" />
    <title>Team Progress</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            background-image: url("../../assets/progressdisplay/bg-4.png");
            background-size: cover;
            color: white;
            font-family: sans-serif;
            overflow: hidden;
        }

        .header {
            padding: 20px;
            text-align: center;
            font-size: 32px;
            background: #2b2b2b;
        }

        .toggle-buttons {
            text-align: center;
            margin: 10px;
        }

        .toggle-buttons button {
            padding: 10px 20px;
            margin: 5px 10px;
            font-size: 16px;
            background: #f1592a;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .toggle-buttons button.active {
            background-color: #fff;
            color: #2b2b2b;
            transform: scale(1.2)
        }

        .chart-container {
            height: calc(100vh - 150px);
            display: none;
            padding: 0 30px;
        }

        .chart-container.active {
            display: block;
        }

        canvas {
            max-height: 100%;
            max-width: 100%;
        }

        .bar-label {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .bar-label img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-bottom: 4px;
        }

        .bar-label span {
            font-size: 14px;
            color: white;
        }

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
            border: 2px solid #3b82f6;
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
            color: #4ade80;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .bonus-popup-content p {
            color: #cbd5e1;
            margin-bottom: 25px;
            font-size: 18px;
            line-height: 1.5;
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
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
        }

        .bonus-btn.secondary {
            background: #334155;
            color: #cbd5e1;
        }

        .bonus-btn:hover:not(.secondary) {
            background: linear-gradient(135deg, #22c55e, #16a34a);
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
</head>

<body>
    <div class="header">📊 Employee Progress Dashboard</div>

    <div class="toggle-buttons">
        <button id="dailyBtn" onclick="showChart('daily')">Daily</button>
        <button id="monthlyBtn" onclick="showChart('monthly')">Monthly</button>
    </div>



    <!-- Bonus Notification Popup -->
    <?php include 'bonus_popup.php'; ?>

    <div id="dailyContainer" class="chart-container active">
        <canvas id="dailyChart"></canvas>
    </div>

    <div id="monthlyContainer" class="chart-container">
        <canvas id="monthlyChart"></canvas>
    </div>

    <script>
        const dailyData = <?= json_encode($dailyData); ?>;
        const monthlyData = <?= json_encode($monthlyData); ?>;

        // Configurable image size - adjust this value to change image size
        const CONFIG_IMAGE_SIZE = 150; // Maximum image size in pixels
        const CONFIG_IMAGE_MARGIN = 8; // Margin from top of green bar

        const imageCache = {};

        function preloadImages(data, callback) {
            let remaining = data.length;
            if (remaining === 0) return callback();

            data.forEach(d => {
                const img = new Image();
                img.src = d.picture;

                img.onload = () => {
                    imageCache[d.picture] = img;
                    if (--remaining === 0) callback();
                };

                img.onerror = () => {
                    if (--remaining === 0) callback();
                };
            });
        }

        function generateSplitBarChart(data, canvasId) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            const names = data.map(d => d.name);
            const images = data.map(d => d.picture);
            const maxValues = data.map(d => Math.max(d.gained, d.allotted));

            // For tooltips
            const gained = data.map(d => d.gained);
            const allotted = data.map(d => d.allotted);

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: names,
                    datasets: [{
                        label: 'Progress',
                        data: maxValues,
                        backgroundColor: '#222', // Placeholder, will be overridden by plugin
                        borderWidth: 0,
                    }],
                    images
                },
                options: {
                    indexAxis: 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            bottom: 30
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutBounce'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: 'white',
                                font: {
                                    size: 16
                                },
                                generateLabels() {
                                    return [{
                                            text: 'Gained',
                                            color: 'white',
                                            fillStyle: '#f1592a',
                                            strokeStyle: '#f1592a',
                                            lineWidth: 0
                                        },
                                        {
                                            text: 'Allotted',
                                            color: 'white',
                                            fillStyle: '#2b2b2b',
                                            strokeStyle: '#2b2b2b',
                                            lineWidth: 0
                                        }
                                    ];
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const i = ctx.dataIndex;
                                    return `Gained: ${gained[i]} / Allotted: ${allotted[i]}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                color: '#fff',
                                callback: () => ''
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: false,
                            ticks: {
                                color: '#fff',
                                font: {
                                    size: 40
                                }
                            },
                            grid: {
                                color: '#ffffffff'
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'split_bar_color',
                    afterDatasetsDraw(chart) {
                        const ctx = chart.ctx;
                        const meta = chart.getDatasetMeta(0);
                        const images = chart.config.data.images;
                        const labels = chart.data.labels;
                        const chartArea = chart.chartArea;
                        const yScale = chart.scales.y;
                        const barWidth = meta.data[0]?.width || 60;

                        data.forEach((d, i) => {
                            const bar = meta.data[i];
                            if (!bar) return;
                            const x = bar.x;
                            const baseY = yScale.getPixelForValue(0);
                            const maxVal = Math.max(d.gained, d.allotted);
                            const minVal = Math.min(d.gained, d.allotted);
                            const isGainedTop = d.gained >= d.allotted;

                            // Calculate split positions
                            let topVal, bottomVal, topColor, bottomColor;
                            if (d.gained === 0) {
                                // Only allotted
                                topVal = maxVal;
                                bottomVal = 0;
                                topColor = '#2b2b2b'; //Yellow Color
                                bottomColor = null;
                            } else if (d.allotted === 0) {
                                // Only gained
                                topVal = maxVal;
                                bottomVal = 0;
                                topColor = '#f1592a'; //Green Color
                                bottomColor = null;
                            } else if (d.gained === d.allotted) {
                                // Equal -> full green bar
                                topVal = d.gained;
                                bottomVal = 0;
                                topColor = '#f1592a'; //Green Color
                                bottomColor = null;
                            } else if (isGainedTop) {
                                topVal = d.gained - d.allotted;
                                bottomVal = d.allotted;
                                topColor = '#f1592a'; //Green Color
                                bottomColor = '#2b2b2b'; //Yellow Color
                            } else {
                                topVal = d.allotted - d.gained;
                                bottomVal = d.gained;
                                topColor = '#2b2b2b'; //Yellow Color
                                bottomColor = '#f1592a'; //Green Color
                            }

                            // Draw bottom segment
                            if (bottomColor && bottomVal > 0) {
                                const y1 = yScale.getPixelForValue(0);
                                const y2 = yScale.getPixelForValue(bottomVal);
                                ctx.save();
                                ctx.fillStyle = bottomColor;
                                ctx.fillRect(x - barWidth / 2, y2, barWidth, y1 - y2);
                                ctx.restore();
                            }
                            // Draw top segment
                            if (topColor && topVal > 0) {
                                const y1 = yScale.getPixelForValue(bottomVal);
                                const y2 = yScale.getPixelForValue(bottomVal + topVal);
                                ctx.save();
                                ctx.fillStyle = topColor;
                                ctx.fillRect(x - barWidth / 2, y2, barWidth, y1 - y2);
                                ctx.restore();
                            }

                            // Draw image and label inside the green (gained) segment at the top
                            const imgSrc = images[i];
                            const image = imageCache[imgSrc];
                            if (d.gained > 0) {
                                // Find the green segment's top and bottom Y
                                let greenTopY, greenBottomY;
                                if (d.allotted === 0 || (isGainedTop && d.gained > d.allotted)) {
                                    // Green is at the very top
                                    greenTopY = yScale.getPixelForValue(bottomVal + topVal); // top of bar
                                    greenBottomY = yScale.getPixelForValue(bottomVal + topVal - d.gained); // bottom of green
                                } else if (!isGainedTop && d.gained < d.allotted) {
                                    // Green is at the bottom
                                    greenTopY = yScale.getPixelForValue(bottomVal); // top of green
                                    greenBottomY = yScale.getPixelForValue(0); // bottom of bar
                                } else {
                                    // fallback
                                    greenTopY = yScale.getPixelForValue(bottomVal + topVal);
                                    greenBottomY = yScale.getPixelForValue(bottomVal);
                                }
                                // Calculate available height for image+label
                                const availableHeight = Math.abs(greenBottomY - greenTopY) - (CONFIG_IMAGE_MARGIN * 2);
                                                            
                                // Calculate max image size (based on barWidth, availableHeight, and config limit)
                                const maxImgSize = Math.min(barWidth * 1.2, CONFIG_IMAGE_SIZE);
                                                            
                                // Final image size: must fit inside bar width & height, never exceed max size
                                const imgSize = Math.max(
                                    0,
                                    Math.min(barWidth, availableHeight * 0.5, maxImgSize) // max 50% of bar height, capped by barWidth
                                );
                                
                                let imgY = greenTopY + CONFIG_IMAGE_MARGIN;
                                if (greenTopY > greenBottomY) imgY = greenBottomY + CONFIG_IMAGE_MARGIN; // handle inverted y axis
                                
                                // Helper: auto-fit text
                                function fitText(ctx, text, maxWidth, maxFontSize = 22, minFontSize = 10) {
                                    let fontSize = maxFontSize;
                                    do {
                                        ctx.font = `bold ${fontSize}px Arial`;
                                        if (ctx.measureText(text).width <= maxWidth) break;
                                        fontSize -= 1;
                                    } while (fontSize > minFontSize);
                                    return fontSize;
                                }
                                
                                // Draw image only if conditions are valid
                                if (x && image && imgSize > 20 && availableHeight > 50) {
                                    // Draw circular image with object-fit: cover
                                    ctx.save();
                                    ctx.beginPath();
                                    ctx.arc(x, imgY + imgSize / 2, imgSize / 2, 0, Math.PI * 2);
                                    ctx.closePath();
                                    ctx.clip();
                                    const scale = Math.sqrt(2);
                                    const drawSize = imgSize * scale;
                                    const drawY = imgY + (imgSize / 2) - (drawSize / 2);
                                    ctx.drawImage(image, x - drawSize / 2, drawY, drawSize, drawSize);
                                    ctx.restore();
                                
                                    // Border around circle
                                    ctx.beginPath();
                                    ctx.arc(x, imgY + imgSize / 2, imgSize / 2, 0, Math.PI * 2);
                                    ctx.strokeStyle = '#fff';
                                    ctx.lineWidth = 2;
                                    ctx.stroke();
                                
                                    // Draw label only if there's enough room below image
                                    if (availableHeight > imgSize + 50) {
                                        ctx.save();
                                        ctx.beginPath();
                                        ctx.rect(x - barWidth / 2, greenTopY, barWidth, availableHeight);
                                        ctx.clip();
                                        ctx.fillStyle = '#fff';
                                        ctx.textAlign = 'center';
                                    
                                        // ✅ Fit first line
                                        const line1 = "Points Gained:";
                                        const fontSize1 = fitText(ctx, line1, barWidth * 0.9); // leave 10% padding
                                        ctx.font = `bold ${fontSize1}px Arial`;
                                        const textY = imgY + imgSize + 20;
                                        ctx.fillText(line1, x, textY);
                                    
                                        // ✅ Fit second line
                                        const line2 = `${d.gained}/${d.allotted}`;
                                        const fontSize2 = fitText(ctx, line2, barWidth * 0.9);
                                        ctx.font = `bold ${fontSize2}px Arial`;
                                        ctx.fillText(line2, x, textY + fontSize1 + 6);
                                    
                                        ctx.restore();
                                    }
                                }
                            }
                            // Name label (always below the bar)
                            ctx.fillStyle = 'white';
                            ctx.font = 'bold 30px Arial';
                            ctx.textAlign = 'center';
                            ctx.fillText(labels[i], x, chartArea.bottom + 25);
                        });
                    }
                }]
            });
            return chart;
        }


        function showChart(type) {
            document.getElementById('dailyBtn').classList.remove('active');
            document.getElementById('monthlyBtn').classList.remove('active');

            document.getElementById('dailyContainer').classList.remove('active');
            document.getElementById('monthlyContainer').classList.remove('active');

            document.getElementById('dailyContainer').style.display = 'none';
            document.getElementById('monthlyContainer').style.display = 'none';

            document.getElementById(type + 'Btn').classList.add('active');
            document.getElementById(type + 'Container').classList.add('active');
            document.getElementById(type + 'Container').style.display = 'block';

            document.getElementById('dailyChart').style.display = 'none';
            document.getElementById('monthlyChart').style.display = 'none';

            document.getElementById(type + 'Chart').style.display = 'block';
            localStorage.setItem('selectedChart', type);
        }

        const lastChart = localStorage.getItem('selectedChart') || 'daily';
        showChart(lastChart);

        // Show bonus popup if bonus data exists
        <?php if ($bonusData): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                showBonusPopup(<?= json_encode($bonusData); ?>);
            }, 1000); // Delay to ensure charts are loaded
        });
        <?php endif; ?>

        preloadImages([...dailyData, ...monthlyData], () => {
            generateSplitBarChart(dailyData, 'dailyChart');
            generateSplitBarChart(monthlyData, 'monthlyChart');
        });

        setTimeout(() => location.reload(), 30000); // Auto-refresh
    </script>

</body>

</html>