<?php 
$page_title = "Coordinator Convertor";
include 'header.php'; 
?>

<style>
body, * {
    font-family: Calibri, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.converter-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background-color: #505050;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.converter-title {
    color: #fff;
    text-align: center;
    margin-bottom: 30px;
    font-size: 24px;
    font-weight: bold;
}

.input-section, .output-section {
    margin-bottom: 25px;
}

.input-section label, .output-section label {
    display: block;
    color: #fff;
    margin-bottom: 8px;
    font-weight: bold;
}

.coordinate-input {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 2px solid #666;
    border-radius: 5px;
    background-color: #3a3a3a;
    color: #fff;
    margin-bottom: 10px;
}

.coordinate-input:focus {
    outline: none;
    border-color: #8b2635;
}

.coordinate-output {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 2px solid #666;
    border-radius: 5px;
    background-color: #2a2a2a;
    color: #00ff00;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    cursor: pointer;
    user-select: all;
}

.convert-btn {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    background-color: #8b2635;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-bottom: 20px;
}

.convert-btn:hover {
    background-color: #6d1d29;
}

.format-examples {
    background-color: #3a3a3a;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.format-examples h3 {
    color: #fff;
    margin-bottom: 10px;
}

.format-examples ul {
    color: #ccc;
    margin-left: 20px;
}

.format-examples li {
    margin-bottom: 5px;
}

.error-message {
    color: #ff6b6b;
    background-color: #4a2c2c;
    padding: 10px;
    border-radius: 5px;
    margin-top: 10px;
    display: none;
}

.success-message {
    color: #4caf50;
    background-color: #2c4a2c;
    padding: 10px;
    border-radius: 5px;
    margin-top: 10px;
    display: none;
}

.debug-panel {
    margin-top: 20px;
    background-color: #333;
    padding: 10px;
    border-radius: 5px;
    display: none;
}

.debug-panel pre {
    color: #aaa;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 12px;
    max-height: 200px;
    overflow-y: auto;
}

.debug-toggle {
    color: #aaa;
    text-align: center;
    margin-top: 10px;
    cursor: pointer;
    font-size: 12px;
}
</style>

<!-- Main Body Content -->
<main class="body-content">
    <div class="converter-container">
        <h1 class="converter-title">Coordinate Converter</h1>
        
        <div class="format-examples">
            <h3>Supported Input Formats:</h3>
            <ul>
                <li>Decimal Degrees: "3.8, 4.8" or "3.8 4.8"</li>
                <li>Degrees Minutes Seconds: "4° 51' 16.47" 3° 51' 8.47""</li>
                <li>Degrees Minutes: "4° 51.275' 3° 51.141'"</li>
                <li>With N/S/E/W: "4°51'16"N 3°51'8"E"</li>
                <li>Mixed formats with various separators and accents</li>
            </ul>
        </div>
        
        <div class="input-section">
            <label for="coordinate-input">Enter Coordinates:</label>
            <input type="text" id="coordinate-input" class="coordinate-input" 
                    placeholder="Enter coordinates in any supported format..." 
                    autocomplete="off">
        </div>
        
        <button id="convert-btn" class="convert-btn">Convert Coordinates</button>
        
        <div class="output-section">
            <label for="coordinate-output">Military Format Output:</label>
            <input type="text" id="coordinate-output" class="coordinate-output" 
                    readonly placeholder="Converted coordinates will appear here..."
                    title="Click to select all text for copying">
        </div>
        
        <div id="error-message" class="error-message"></div>
        <div id="success-message" class="success-message"></div>
        
        <div class="debug-toggle" id="debug-toggle">Show Debug Info</div>
        <div class="debug-panel" id="debug-panel">
            <pre id="debug-output"></pre>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // Debug mode toggle
    $('#debug-toggle').click(function() {
        $('#debug-panel').toggle();
        const isVisible = $('#debug-panel').is(':visible');
        $(this).text(isVisible ? 'Hide Debug Info' : 'Show Debug Info');
    });

    // Convert coordinates on button click
    $('#convert-btn').click(function() {
        convertCoordinates();
    });
    
    // Convert on Enter key press
    $('#coordinate-input').keypress(function(e) {
        if (e.which == 13) {
            convertCoordinates();
        }
    });
    
    // Select all text when clicking output
    $('#coordinate-output').click(function() {
        this.select();
        document.execCommand('copy');
        showMessage('Coordinates copied to clipboard!', 'success');
    });
    
    function convertCoordinates() {
        const input = $('#coordinate-input').val().trim();
        
        // Clear debug output
        $('#debug-output').html('');
        debugLog('Input: ' + input);
        
        if (!input) {
            showMessage('Please enter coordinates to convert.', 'error');
            return;
        }
        
        try {
            const result = parseAndConvert(input);
            $('#coordinate-output').val(result);
            showMessage('Conversion successful!', 'success');
        } catch (error) {
            showMessage('Error: ' + error.message, 'error');
            $('#coordinate-output').val('');
        }
    }
    
    function parseAndConvert(input) {
        // Clean input - remove non-standard degree symbols and collapse multiple spaces.
        // The regexes below rely on single/double quotes, so we leave them alone.
        let cleaned = input.replace(/[°º]/g, '°')
                          .replace(/\s+/g, ' ')
                          .trim();
        
        debugLog('Cleaned input: ' + cleaned);
        
        // Try different parsing methods
        let coords = null;
        
        // Method 1: Decimal degrees (3.8, 4.8 or 3.8 4.8)
        coords = parseDecimalDegrees(cleaned);
        if (coords) {
            debugLog('Parsed as decimal degrees: ' + JSON.stringify(coords));
            return formatOutput(coords.lat, coords.lon);
        }
        
        // Method 2: DMS format (4° 51' 16.47" 3° 51' 8.47")
        coords = parseDMS(cleaned);
        if (coords) {
            debugLog('Parsed as DMS: ' + JSON.stringify(coords));
            return formatOutput(coords.lat, coords.lon);
        }
        
        // Method 3: Degrees Minutes format (4° 51.275' 3° 51.141')
        coords = parseDegreesMinutes(cleaned);
        if (coords) {
            debugLog('Parsed as Degrees Minutes: ' + JSON.stringify(coords));
            return formatOutput(coords.lat, coords.lon);
        }
        
        throw new Error('Unable to parse coordinate format. Please check the input format.');
    }
    
    function parseDecimalDegrees(input) {
        // Match decimal degrees: 3.8, 4.8 or 3.8 4.8 or similar
        const regex = /^(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)$/;
        const match = input.match(regex);
        
        if (match) {
            debugLog('Decimal degrees match: ' + JSON.stringify(match));
            const lat = parseFloat(match[1]);
            const lon = parseFloat(match[2]);
            
            if (Math.abs(lat) <= 90 && Math.abs(lon) <= 180) {
                return { lat: lat, lon: lon };
            }
        }
        debugLog('Not decimal degrees format');
        return null;
    }
    
    function parseDMS(input) {
        // The regex for DMS should now work correctly since we're not modifying quotes in cleaning.
        // It looks for a degrees sign, minutes quote, and seconds quote, with flexible spaces.
        const dmsRegex = /(\d+)°\s*(\d+)['′]\s*(\d+(?:\.\d+)?)["″]?\s*([NSEW])?/g;
        
        debugLog('Trying DMS regex on: ' + input);
        
        const matches = [];
        let match;
        while ((match = dmsRegex.exec(input)) !== null) {
            debugLog('DMS match found: ' + JSON.stringify(match));
            matches.push(match);
        }
        
        debugLog('Total DMS matches: ' + matches.length);
        
        if (matches.length >= 2) {
            const coord1 = dmsToDecimal(
                parseInt(matches[0][1]), 
                parseInt(matches[0][2]), 
                parseFloat(matches[0][3]), 
                matches[0][4]
            );
            
            const coord2 = dmsToDecimal(
                parseInt(matches[1][1]), 
                parseInt(matches[1][2]), 
                parseFloat(matches[1][3]), 
                matches[1][4]
            );
            
            debugLog('Coord1: ' + coord1 + ', Coord2: ' + coord2);
            
            // Determine which is lat/lon based on values or directions
            let lat, lon;
            if (matches[0][4] && (matches[0][4] === 'N' || matches[0][4] === 'S')) {
                lat = matches[0][4] === 'S' ? -coord1 : coord1;
                lon = matches[1][4] === 'W' ? -coord2 : coord2;
            } else if (Math.abs(coord1) <= 90) {
                lat = coord1;
                lon = coord2;
            } else {
                lat = coord2;
                lon = coord1;
            }
            
            return { lat: lat, lon: lon };
        }
        debugLog('Not DMS format or insufficient matches');
        return null;
    }
    
    function parseDegreesMinutes(input) {
        // Match Degrees Minutes format: 4° 51.275' 3° 51.141'
        const dmRegex = /(\d+)°\s*(\d+(?:\.\d+)?)['′]\s*([NSEW])?/g;
        
        debugLog('Trying DM regex on: ' + input);
        
        const matches = [];
        let match;
        while ((match = dmRegex.exec(input)) !== null) {
            debugLog('DM match found: ' + JSON.stringify(match));
            matches.push(match);
        }
        
        debugLog('Total DM matches: ' + matches.length);
        
        if (matches.length >= 2) {
            const coord1 = parseInt(matches[0][1]) + parseFloat(matches[0][2]) / 60;
            const coord2 = parseInt(matches[1][1]) + parseFloat(matches[1][2]) / 60;
            
            debugLog('DM Coord1: ' + coord1 + ', Coord2: ' + coord2);
            
            let lat, lon;
            if (matches[0][3] && (matches[0][3] === 'N' || matches[0][3] === 'S')) {
                lat = matches[0][3] === 'S' ? -coord1 : coord1;
                lon = matches[1][3] === 'W' ? -coord2 : coord2;
            } else if (Math.abs(coord1) <= 90) {
                lat = coord1;
                lon = coord2;
            } else {
                lat = coord2;
                lon = coord1;
            }
            
            return { lat: lat, lon: lon };
        }
        debugLog('Not DM format or insufficient matches');
        return null;
    }
    
    function dmsToDecimal(degrees, minutes, seconds, direction) {
        let decimal = degrees + minutes / 60 + seconds / 3600;
        if (direction === 'S' || direction === 'W') {
            decimal = -decimal;
        }
        return decimal;
    }
    
    function formatOutput(lat, lon) {
        // Convert to military format: "04 48 00 N 003 48 00 E"
        const latDir = lat >= 0 ? 'N' : 'S';
        const lonDir = lon >= 0 ? 'E' : 'W';
        
        lat = Math.abs(lat);
        lon = Math.abs(lon);
        
        // Convert to DMS
        const latDeg = Math.floor(lat);
        const latMinDecimal = (lat - latDeg) * 60;
        const latMin = Math.floor(latMinDecimal);
        const latSec = Math.round((latMinDecimal - latMin) * 60);
        
        const lonDeg = Math.floor(lon);
        const lonMinDecimal = (lon - lonDeg) * 60;
        const lonMin = Math.floor(lonMinDecimal);
        const lonSec = Math.round((lonMinDecimal - lonMin) * 60);
        
        // Handle cases where seconds round to 60
        let adjustedLatMin = latMin;
        let adjustedLatSec = latSec;
        if (latSec === 60) {
            adjustedLatSec = 0;
            adjustedLatMin += 1;
            // If minutes become 60, adjust degrees
            if (adjustedLatMin === 60) {
                adjustedLatMin = 0;
                lat = latDeg + 1;
            }
        }
        
        let adjustedLonMin = lonMin;
        let adjustedLonSec = lonSec;
        if (lonSec === 60) {
            adjustedLonSec = 0;
            adjustedLonMin += 1;
            // If minutes become 60, adjust degrees
            if (adjustedLonMin === 60) {
                adjustedLonMin = 0;
                lon = lonDeg + 1;
            }
        }
        
        // Format with leading zeros
        const formattedLat = String(latDeg).padStart(2, '0') + ' ' + 
                                String(adjustedLatMin).padStart(2, '0') + ' ' + 
                                String(adjustedLatSec).padStart(2, '0') + ' ' + latDir;
        
        const formattedLon = String(lonDeg).padStart(3, '0') + ' ' + 
                                String(adjustedLonMin).padStart(2, '0') + ' ' + 
                                String(adjustedLonSec).padStart(2, '0') + ' ' + lonDir;
        
        return formattedLat + ' ' + formattedLon;
    }
    
    function showMessage(message, type) {
        $('.error-message, .success-message').hide();
        
        if (type === 'error') {
            $('#error-message').text(message).show();
        } else {
            $('#success-message').text(message).show();
        }
        
        // Hide message after 3 seconds
        setTimeout(function() {
            $('.error-message, .success-message').fadeOut();
        }, 3000);
    }
    
    function debugLog(message) {
        const currentContent = $('#debug-output').html();
        $('#debug-output').html(currentContent + message + '\n');
    }
});
</script>

<?php include 'footer.php'; ?>
