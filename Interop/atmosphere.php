<?php
function get_client_ip(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function get_url(string $url, int $timeout = 8): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'Atmosphere/1.0 (+http://webetu)',
    ]);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode === 200 && $data !== false) ? $data : null;
}

// Géolocalisation
$geoUrl = "http://ip-api.com/xml/";
$geoXmlString = get_url($geoUrl);
$lat = 48.6895;
$lon = 6.1902;
$city = 'Nancy';
$zip = '54000';
$country = 'France';
if ($geoXmlString) {
    $geo = simplexml_load_string($geoXmlString);
    if ((string) $geo->status === 'success') {
        $lat = (float) $geo->lat;
        $lon = (float) $geo->lon;
        $city = (string) $geo->city;
        $zip = (string) $geo->zip;
        $country = (string) $geo->country;
    }
}

// Météo 
$meteoUrl = "http://www.infoclimat.fr/public-api/gfs/xml?_ll=$lat,$lon&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";
$meteoApi = get_url($meteoUrl);
$meteoXmlString = '<?xml version="1.0"?><!DOCTYPE meteo SYSTEM "meteo.dtd"><meteo><jour date="' . date('Y-m-d') . '"><ville>Nancy</ville><matin><temperature>3</temperature><temps type="pluie">Pluie</temps><vent force="modere"/></matin><midi><temperature>6</temperature><temps type="nuageux">Nuageux</temps><vent force="faible"/></midi><soir><temperature>2</temperature><temps type="nuageux">Nuageux</temps><vent force="modere"/></soir></jour></meteo>';

// XSL Météo
$meteoXml = new DOMDocument();
$meteoXml->loadXML($meteoXmlString);
$xslDoc = new DOMDocument();
$xslDoc->load('meteo.xsl');
$proc = new XSLTProcessor();
$proc->importStylesheet($xslDoc);
$meteoHtml = $proc->transformToXML($meteoXml);

// Trafic 
$traficUrl = "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=1%3D1&outFields=*&f=geojson";
$traficJson = get_url($traficUrl);

// Covid
$covidUrl = "https://odisse.santepubliquefrance.fr/explore/dataset/sum-eau-indicateurs/export/?flg=fr-fr&disjunctive.nom_station=1&refine.departement=54";
$covidData = get_url($covidUrl);
$covidDates = ['28/12', '29/12', '30/12', '31/12', '01/01', '02/01', '03/01'];
$covidValues = [0.4, 0.6, 0.9, 1.1, 0.8, 0.5, 0.7];
if ($covidData) {
    $lines = explode("\n", $covidData);
    $covidDates = $covidValues = [];
    for ($i = 1; $i < min(11, count($lines)); $i++) {
        $fields = str_getcsv($lines[$i], ';');
        if (count($fields) > 5) {
            $covidDates[] = substr($fields[0], 8, 5);
            $covidValues[] = (float) $fields[5];
        }
    }
}

// Air
$airUrl = "https://services3.arcgis.com/Is0UwT37raQYl9Jj/arcgis/rest/services/ind_grandest/FeatureServer/0/query?where=lib_zone%3D%27Nancy%27&outFields=*&f=json";
$airData = get_url($airUrl);
$airIndice = 2;
$airLibelle = 'Moyen';
if ($airData) {
    $air = json_decode($airData, true);
    if (!empty($air['features'][0]['attributes'])) {
        $airIndice = $air['features'][0]['attributes']['indice'] ?? 2;
        $airLibelle = $air['features'][0]['attributes']['libelle'] ?? 'Moyen';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atmosphere</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }

        .info {
            background: linear-gradient(135deg, #f0f8ff 0%, #e3f2fd 100%);
            padding: 25px;
            border-radius: 12px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #f44336;
        }

        h1 {
            color: #1976d2;
            text-align: center;
            margin-bottom: 30px;
        }

        h2 {
            color: #424242;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .coords {
            font-size: 1.2em;
            font-weight: bold;
            color: #388e3c;
        }

        .resources {
            font-size: 0.9em;
            opacity: 0.8;
        }

        @media (max-width:600px) {
            body {
                padding: 10px;
            }

            .info {
                padding: 15px;
            }
        }

        .meteo-resume {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ff9800;
        }

        .periode {
            margin-bottom: 15px;
            padding: 12px;
            background: white;
            border-radius: 5px;
        }

        .moment {
            margin: 6px 0;
        }

        .temp {
            font-weight: bold;
            color: #d84315;
        }

        .temps {
            font-weight: 500;
        }

        .vent {
            color: #666;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel=" stylesheet"
        href="https://odisse.santepubliquefrance.fr/explore/dataset/sum-eau-indicateurs/export/?flg=fr-fr" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
    <h1>Atmosphere</h1>
    <p style="text-align:center;color:#666;margin-bottom:30px;">État de l'atmosphère pour décider d'utiliser sa voiture
    </p>

    <section>
        <h2> Géolocalisation</h2>
        <div class="info">
            <h3>Ville: <?= htmlspecialchars($city) ?></h3>
            <p>Coordonnées: <?= number_format($lat, 4) ?>°N, <?= number_format($lon, 4) ?>°E</p>
            <p>Code postal: <?= htmlspecialchars($zip) ?></p>
            <p>Pays: <?= htmlspecialchars($country) ?></p>
            <p class="resources">
                API: <a href="<?= htmlspecialchars($geoUrl) ?>">ip-api.com</a> |
                Git: <a href="https://github.com/haanaan/Projet-interoperabilte.git">github</a>
            </p>
        </div>

    </section>

    <section>
        <h2>Météo</h2>
        <?php if (isset($meteoHtml)): ?>
            <div class="resources">API: <a href="<?= htmlspecialchars($meteoUrl) ?>">Infoclimat</a></div>
            <?= $meteoHtml ?>
        <?php endif; ?>
    </section>

    <section>
        <h2>Trafic Grand Nancy</h2>
        <div id="map" style="height:400px;border-radius:8px;overflow:hidden;"></div>
        <p class="resources">
            API: <a href="<?= htmlspecialchars($traficUrl) ?>">ATMO Grand Est</a>
        </p>
    </section>

    <script>
        const map = L.map('map').setView([<?= $lat ?>, <?= $lon ?>], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '©OSM' }).addTo(map);
        L.marker([<?= $lat ?>, <?= $lon ?>]).addTo(map).bindPopup('Votre position');
        <?php if ($traficJson): ?>
            const trafic = <?= $traficJson ?>;
            L.geoJSON(trafic, {
                onEachFeature: (f, l) => { l.bindPopup(f.properties.lib_zone || 'Zone') },
                pointToLayer: (f, latlng) => L.circleMarker(latlng, { radius: 8, fillColor: '#ff5722' })
            }).addTo(map);
        <?php endif; ?>
    </script>

    <section>
        <h2>Covid - Eaux usées</h2>
        <canvas id="covidChart" style="max-height:300px;"></canvas>
        <p class="resources">Source: <a href="<?= htmlspecialchars($covidUrl) ?>">SUM'eau SPF</a></p>
    </section>

    <script>
        const ctx = document.getElementById('covidChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_reverse($covidDates)) ?>,
                datasets: [{
                    label: 'Charge virale SARS-CoV-2',
                    data: <?= json_encode(array_reverse($covidValues)) ?>,
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244,67,54,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>

    <section>
        <h2>Qualité de l'air</h2>
        <div class="info">
            <h3>Indice ATMO: <strong><?= $airIndice ?></strong></h3>
            <p>État: <?= htmlspecialchars($airLibelle) ?></p>
            <p class="resources">
                Source: <a href="<?= htmlspecialchars($airUrl) ?>">ATMO Grand Est</a>
            </p>
        </div>
    </section>


</body>

</html>