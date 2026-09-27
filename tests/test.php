<?php
// progress_display.php
session_start();
include 'db.php';
date_default_timezone_set('Asia/Karachi');

function getEmployeeProgress($type)
{
    global $conn;

    if ($type === 'daily') {
        $where = "DATE(assigned_time) = CURDATE()";
    } else {
        $where = "MONTH(assigned_time) = MONTH(CURDATE()) AND YEAR(assigned_time) = YEAR(CURDATE())";
    }

    $query = "
        SELECT designer_name, SUM(allotted_time) AS total_allotted, SUM(points_gained) AS total_gained, u.profile_picture
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
            'picture' => $row['profile_picture'] ?: 'images/default_profile.png',
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
    <title>Team Progress</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            background: #0f172a;
            color: white;
            font-family: sans-serif;
            overflow: hidden;
        }

        .header {
            padding: 20px;
            text-align: center;
            font-size: 32px;
            background: #1e293b;
        }

        .toggle-buttons {
            text-align: center;
            margin: 10px;
        }

        .toggle-buttons button {
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            background: #3b82f6;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        .toggle-buttons button.active {
            background-color: #4ade80;
            color: white;
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
    </style>
</head>

<body>

    <div class="header">📊 Employee Progress Dashboard</div>

    <div class="toggle-buttons">
        <button id="dailyBtn" onclick="showChart('daily')">Daily</button>
        <button id="monthlyBtn" onclick="showChart('monthly')">Monthly</button>
    </div>

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
                                            fillStyle: '#22c55e',
                                            strokeStyle: '#22c55e',
                                            lineWidth: 0
                                        },
                                        {
                                            text: 'Allotted',
                                            color: 'white',
                                            fillStyle: '#facc15',
                                            strokeStyle: '#facc15',
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
                                color: 'white',
                                callback: () => ''
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: false,
                            ticks: {
                                color: 'white',
                                font: {
                                    size: 40
                                }
                            },
                            grid: {
                                color: '#334155'
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
                                topColor = '#facc15';
                                bottomColor = null;
                            } else if (d.allotted === 0) {
                                // Only gained
                                topVal = maxVal;
                                bottomVal = 0;
                                topColor = '#22c55e';
                                bottomColor = null;
                            } else if (isGainedTop) {
                                topVal = d.gained - d.allotted;
                                bottomVal = d.allotted;
                                topColor = '#22c55e';
                                bottomColor = '#facc15';
                            } else {
                                topVal = d.allotted - d.gained;
                                bottomVal = d.gained;
                                topColor = '#facc15';
                                bottomColor = '#22c55e';
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
                                const maxImgSize = Math.min(barWidth * 1.2, CONFIG_IMAGE_SIZE);
                                const imgSize = Math.max(availableHeight > 50 ? Math.min(maxImgSize, availableHeight - 40) : 0, 30);
                                let imgY = greenTopY + CONFIG_IMAGE_MARGIN;
                                if (greenTopY > greenBottomY) imgY = greenBottomY + CONFIG_IMAGE_MARGIN; // handle inverted y axis
                                if (x && image && imgSize > 25 && availableHeight > 50) {
                                    ctx.save();
                                    ctx.beginPath();
                                    ctx.arc(x, imgY + imgSize / 2, imgSize / 2, 0, Math.PI * 2);
                                    ctx.closePath();
                                    ctx.clip();
                                    ctx.drawImage(image, x - imgSize / 2, imgY, imgSize, imgSize);
                                    ctx.restore();
                                    // Border
                                    ctx.beginPath();
                                    ctx.arc(x, imgY + imgSize / 2, imgSize / 2, 0, Math.PI * 2);
                                    ctx.strokeStyle = '#fff';
                                    ctx.lineWidth = 2;
                                    ctx.stroke();
                                    // Label: Gained: 5/10 (directly below image, inside green bar)
                                    ctx.save();
                                    ctx.beginPath();
                                    ctx.rect(x - barWidth / 2, greenTopY, barWidth, availableHeight);
                                    ctx.clip();
                                    ctx.fillStyle = '#fff';
                                    ctx.font = 'bold 22px Arial';
                                    ctx.textAlign = 'center';
                                    ctx.fillText(`Gained: ${d.gained}/${d.allotted}`, x, imgY + imgSize + 24);
                                    ctx.restore();
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

        preloadImages([...dailyData, ...monthlyData], () => {
            let dailyChart = generateSplitBarChart(dailyData, 'dailyChart');
            let monthlyChart = generateSplitBarChart(monthlyData, 'monthlyChart');
        });

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

        setTimeout(() => location.reload(), 60000); // Auto-refresh
    </script>

</body>

</html>