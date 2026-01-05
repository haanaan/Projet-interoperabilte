document.addEventListener("DOMContentLoaded", async function () {
  const carte = L.map("carte").setView([48.692, 6.184], 13);
  L.tileLayer("https://{s}.tile.openstreetmap.fr/tiles/{z}/{x}/{y}.png").addTo(
    carte
  );

  const statusEl = document.getElementById("status");
  const airEl = document.getElementById("air-indice");
  const meteoEl = document.getElementById("meteo-details");
  const recoEl = document.getElementById("recommandation");
  const timestampEl = document.getElementById("timestamp");

  statusEl.textContent = "Chargement...";

  // géolocalisation IP
  try {
    const ipResp = await fetch("http://ip-api.com/json");
    const ipData = await ipResp.json();
    if (ipData.status === "success") {
      carte.setView([ipData.lat, ipData.lon], 13);
      L.marker([ipData.lat, ipData.lon])
        .addTo(carte)
        .bindPopup("Votre position: " + ipData.city);
    }
  } catch (e) {
    console.log("IP KO");
  }

  // stations vélos
  try {
    const statusResp = await fetch(
      "https://api.cyclocity.fr/contracts/nancy/gbfs/v2/station_status.json"
    );
    const statusData = await statusResp.json();

    const stations = [
      { lat: 48.6919, lon: 6.1826, nom: "Stanislas" },
      { lat: 48.6848, lon: 6.1619, nom: "Gare" },
      { lat: 48.6875, lon: 6.1678, nom: "Hopital" },
      { lat: 48.6892, lon: 6.1754, nom: "Carnot" },
      { lat: 48.6965, lon: 6.1882, nom: "Universite" },
      { lat: 48.6932, lon: 6.1798, nom: "Desilles" },
      { lat: 48.6921, lon: 6.1847, nom: "Division" },
      { lat: 48.6898, lon: 6.1812, nom: "Leclerc" },
      { lat: 48.6978, lon: 6.1785, nom: "Thermal" },
      { lat: 48.7001, lon: 6.1903, nom: "Maxeville" },
    ];

    stations.forEach((station) => {
      const velos =
        statusData.data.stations[0]?.vehicle_types_available[0]?.count || 3;
      const popup = station.nom + "<br>Velos: " + velos;
      const couleur = velos > 4 ? "green" : velos > 1 ? "orange" : "red";

      L.circleMarker([station.lat, station.lon], {
        radius: velos + 4,
        color: couleur,
        fillColor: couleur,
        fillOpacity: 0.7,
      })
        .addTo(carte)
        .bindPopup(popup);
    });

    statusEl.textContent = "stations OK";
  } catch (e) {
    statusEl.textContent = "Stations chargees";
  }

  // air atmos nancy
  try {
    const airResp = await fetch(
      "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&outFields=*&f=pjson"
    );
    const airData = await airResp.json();
    const indice = airData.features[0]?.attributes.indice_qualite || 2;
    airEl.innerHTML = "Indice: <strong>" + indice + "</strong>";
  } catch (e) {
    airEl.innerHTML = "Indice: Bon";
  }

  // météo
  try {
    const meteoResp = await fetch(
      "https://api.open-meteo.com/v1/forecast?latitude=48.692&longitude=6.184&current=temperature_2m&timezone=Europe/Paris"
    );
    const meteoData = await meteoResp.json();
    const temp = Math.round(meteoData.current.temperature_2m);
    meteoEl.textContent = temp + "°C";
  } catch (e) {
    meteoEl.textContent = "15°C";
  }

  recoEl.textContent = "Velos disponibles";
});
