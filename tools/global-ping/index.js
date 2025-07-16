import { pingHost } from './backend.js';

let map;
let markersLayer;
let currentTab = "html";
let lastQuery = "";
let lastPings = [];

const greenIcon = L.divIcon({
    html: `<svg height="12" width="12" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="6" r="5" stroke="#fff" stroke-width="1.5" fill="#10b981" /></svg>`,
    className: '', iconSize: [12, 12], iconAnchor: [6, 6]
});

const redIcon = L.divIcon({
    html: `<svg height="12" width="12" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="6" r="5" stroke="#fff" stroke-width="1.5" fill="#ef4444" /></svg>`,
    className: '', iconSize: [12, 12], iconAnchor: [6, 6]
});

function buildUI(app) {
    const container = document.createElement("div");
    container.className = "container";

    container.innerHTML = `
        <h1 class="title">Global Ping Test</h1>
        <p class="description">Measure network latency from multiple international locations to your server. Valuable for evaluating global performance, troubleshooting regional access issues, and CDN optimization.</p>
        <div id="map"></div>
        <form class="search-form">
            <input type="text" class="search-input" name="host" placeholder="Enter hostname or IP (e.g. 1.1.1.1)" required>
            <button type="submit" class="ping-button">Ping</button>
        </form>
        <div class="tabs-wrapper" style="display: none;">
            <div class="tabs">
                <a class="tab active" data-tab="html">HTML</a>
                <a class="tab" data-tab="json">JSON</a>
            </div>
            <div class="results-wrapper"></div>
        </div>
    `;
    app.appendChild(container);

    container.querySelector('.search-form').addEventListener('submit', handleFormSubmit);
    container.querySelectorAll('.tab').forEach(tab => tab.addEventListener('click', handleTabSwitch));

    return container;
}

function initializeMap() {
    map = L.map('map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    markersLayer = new L.LayerGroup().addTo(map);
}

function updateMap(pings) {
    markersLayer.clearLayers();
    if (!pings || pings.length === 0) return;

    pings.forEach(ping => {
        if (typeof ping.latitude === 'number' && typeof ping.longitude === 'number') {
            const icon = ping.status === 'Succeeded' ? greenIcon : redIcon;
            const marker = L.marker([ping.latitude, ping.longitude], { icon: icon });
            marker.bindPopup(`<b>${escapeHtml(ping.location)}</b><br>Status: ${escapeHtml(ping.status)}<br>Avg Latency: ${escapeHtml(ping.avg)}`);
            marker.addTo(markersLayer);
        }
    });
}

async function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const input = form.querySelector('.search-input');
    const button = form.querySelector('.ping-button');
    const query = input.value.trim();
    if (!query) return;

    const tabsWrapper = document.querySelector('.tabs-wrapper');
    const resultsWrapper = document.querySelector('.results-wrapper');
    tabsWrapper.style.display = 'block';
    resultsWrapper.innerHTML = `<div class="loading-spinner"></div>`;
    
    button.disabled = true;
    input.disabled = true;

    try {
        const pings = await pingHost(query);
        lastQuery = query;
        lastPings = pings;
        updateMap(pings);
        renderResults();
    } catch (error) {
        lastPings = [{ continent: "Error", location: "Error", ip: query, status: "Error", avg: error.message, min: "N/A", max: "N/A", loss: "N/A" }];
        renderResults();
    } finally {
        button.disabled = false;
        input.disabled = false;
    }
}

function handleTabSwitch(event) {
    currentTab = event.currentTarget.dataset.tab;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.currentTarget.classList.add('active');
    renderResults();
}

function renderResults() {
    const resultsWrapper = document.querySelector('.results-wrapper');
    resultsWrapper.innerHTML = ''; // Clear spinner or old content

    if (!lastQuery) return;

    const header = document.createElement("div");
    header.className = "results-header";
    header.innerHTML = `Ping Results for <b>${escapeHtml(lastQuery)}</b>`;
    resultsWrapper.appendChild(header);

    if (currentTab === "json") {
        renderJsonView(resultsWrapper);
    } else {
        renderTableView(resultsWrapper);
    }
}

function renderJsonView(container) {
    const pre = document.createElement("pre");
    pre.className = 'json-pre';
    const jsonData = { query: { tool: "global-ping", host: lastQuery }, response: lastPings };
    pre.textContent = JSON.stringify(jsonData, null, 2);
    container.appendChild(pre);
}

function renderTableView(container) {
    const table = document.createElement("table");
    table.className = "results-table";
    table.innerHTML = `
        <thead>
            <tr>
                <th>Location</th><th>IP Address</th><th>Status</th><th>Min</th><th>Avg</th><th>Max</th><th>Loss</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;
    const tbody = table.querySelector('tbody');

    if (!lastPings || lastPings.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;">No results to display.</td></tr>`;
        container.appendChild(table);
        return;
    }

    lastPings.sort((a, b) => (a.continent + a.location).localeCompare(b.continent + b.location));

    const grouped = lastPings.reduce((acc, ping) => {
        const continent = ping.continent || 'Unknown';
        if (!acc[continent]) acc[continent] = [];
        acc[continent].push(ping);
        return acc;
    }, {});

    for (const continent in grouped) {
        const groupRow = tbody.insertRow();
        groupRow.className = 'group-row';
        groupRow.innerHTML = `<td colspan="7">${escapeHtml(continent)}</td>`;

        grouped[continent].forEach(row => {
            const rowEl = tbody.insertRow();
            rowEl.innerHTML = `
                <td>${escapeHtml(row.location)}</td>
                <td>${escapeHtml(row.ip)}</td>
                <td class="status-${row.status.toLowerCase()}">${escapeHtml(row.status)}</td>
                <td>${escapeHtml(row.min)}</td>
                <td>${escapeHtml(row.avg)}</td>
                <td>${escapeHtml(row.max)}</td>
                <td>${escapeHtml(row.loss)}</td>
            `;
        });
    }
    container.appendChild(table);
    
    if (lastPings.some(p => p.status !== 'Succeeded')) {
        const note = document.createElement('p');
        note.className = 'results-note';
        note.innerHTML = `<b>Note:</b> A "Failed" status or high packet loss can be due to network congestion or a firewall blocking ICMP requests. Test a known responsive host like code>1.1.1.1</code> to verify tool functionality.`;
        container.appendChild(note);
    }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>"']/g, (tag) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;' 
  }[tag] || tag));
}
const app = document.getElementById("app");
buildUI(app);
initializeMap();
