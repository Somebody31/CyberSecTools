const GLOBALPING_API_BASE_URL = "https://api.globalping.io/v1";

async function initiatePingMeasurement(target) {
    const headers = { "Content-Type": "application/json" };
    const payload = {
        type: "ping",
        target: target,
        locations: [
            {
                magic: "IN",
                limit: 2
            },
            {
                magic: "world",
                limit: 18
            }
        ]
    };

    try {
        const response = await fetch(`${GLOBALPING_API_BASE_URL}/measurements`, {
            method: "POST",
            headers: headers,
            body: JSON.stringify(payload),
            signal: AbortSignal.timeout(15000)
        });

        if (!response.ok) {
            const errorText = await response.text();
            try {
                const errorJson = JSON.parse(errorText);
                throw new Error(`API error: ${response.status} - ${errorJson.error.message}`);
            } catch (e) {
                throw new Error(`API error: ${response.status} - ${errorText}`);
            }
        }

        const data = await response.json();
        return data.id || null;

    } catch (error) {
        console.error("Error initiating measurement:", error);
        throw error;
    }
}

async function getMeasurementResults(measurementId) {
    const maxAttempts = 60;
    let attempts = 0;

    while (attempts < maxAttempts) {
        try {
            const response = await fetch(`${GLOBALPING_API_BASE_URL}/measurements/${measurementId}`, {
                signal: AbortSignal.timeout(30000)
            });

            if (!response.ok) {
                throw new Error(`API returned an error: ${response.status}`);
            }

            const data = await response.json();
            if (data.status === "finished") {
                return data.results;
            }

            if (data.status === "failed") {
                throw new Error(data.error || 'Unknown measurement error');
            }

            await new Promise(resolve => setTimeout(resolve, 3000));
            attempts++;

        } catch (error) {
            if (error.name === 'TimeoutError') {
                console.warn(`Polling request timed out. Retrying...`);
            } else {
                console.error(`Error fetching results:`, error);
            }
            attempts++;
            await new Promise(resolve => setTimeout(resolve, 3000));
        }
    }

    throw new Error("Measurement timed out after maximum attempts.");
}

export async function pingHost(target) {
    try {
        const measurementId = await initiatePingMeasurement(target);
        if (!measurementId) {
            throw new Error("Could not initiate measurement.");
        }
        
        const rawResults = await getMeasurementResults(measurementId);

        if (!rawResults || rawResults.length === 0) {
            return [];
        }

        return rawResults.map(result => {
            const { probe = {}, result: pingData = {}, error } = result;
            const { city = 'N/A', country = 'N/A', continent = 'N/A', latitude, longitude } = probe;
            const { stats, resolvedAddress } = pingData || {};

            const location = `${city}, ${country}`;
            const ipAddress = resolvedAddress || target;
            const baseResult = { continent, location, ip: ipAddress, latitude, longitude };

            if (stats && typeof stats.avg === 'number') {
                return {
                    ...baseResult,
                    status: "Succeeded",
                    avg: `${stats.avg.toFixed(2)}ms`,
                    min: `${stats.min.toFixed(2)}ms`,
                    max: `${stats.max.toFixed(2)}ms`,
                    loss: `${stats.loss}%`
                };
            } else {
                return {
                    ...baseResult,
                    status: "Failed",
                    avg: error || "Request Timed Out",
                    min: "N/A", max: "N/A", loss: "100%"
                };
            }
        });

    } catch (error) {
        console.error("Overall pingHost error:", error);
        return [{
            continent: "Error", location: "Error", ip: target, status: "Error", avg: error.message, min: "N/A", max: "N/A", loss: "N/A"
        }];
    }
}
