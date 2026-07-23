<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport flotte — {{ $project }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; margin: 2cm; }
        h1 { font-size: 20px; margin-bottom: 0.25rem; }
        .meta { color: #666; margin-bottom: 1.5rem; font-size: 11px; }
        h2 { font-size: 14px; margin-top: 1.5rem; border-bottom: 1px solid #ccc; padding-bottom: 0.25rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .stats { display: flex; gap: 2rem; margin: 1rem 0; }
        .stat { font-size: 13px; }
        .stat strong { display: block; font-size: 18px; }
        @media print { body { margin: 1cm; } }
    </style>
</head>
<body>
    <h1>{{ $project }} — Rapport de flotte</h1>
    <p class="meta">Auteur : {{ $author }} · Généré le {{ $generatedAt }} · AutoChain Emma+</p>

    <div class="stats">
        <div class="stat"><strong>{{ $vehicles->count() }}</strong> véhicules</div>
        <div class="stat"><strong>{{ $alerts->count() }}</strong> alertes actives (aperçu)</div>
        <div class="stat"><strong>{{ number_format($maintenanceTotal, 2, ',', ' ') }} €</strong> coûts maintenance</div>
        <div class="stat"><strong>{{ $maintenanceCount }}</strong> interventions</div>
    </div>

    <h2>Véhicules</h2>
    <table>
        <thead>
            <tr>
                <th>Immat.</th><th>Marque / Modèle</th><th>Km</th><th>Statut</th><th>Carburant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $v)
            <tr>
                <td>{{ $v->license_plate }}</td>
                <td>{{ $v->brand }} {{ $v->model }} ({{ $v->year }})</td>
                <td>{{ number_format($v->current_mileage, 0, ',', ' ') }}</td>
                <td>{{ $v->status->value ?? $v->status }}</td>
                <td>{{ $v->fuel_type }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Alertes non résolues</h2>
    <table>
        <thead><tr><th>Titre</th><th>Véhicule</th><th>Message</th></tr></thead>
        <tbody>
            @forelse($alerts as $a)
            <tr>
                <td>{{ $a->title }}</td>
                <td>{{ $a->vehicle?->license_plate }}</td>
                <td>{{ $a->message }}</td>
            </tr>
            @empty
            <tr><td colspan="3">Aucune alerte active</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="meta" style="margin-top:2rem">Document généré par AutoChain Emma+ — Traçabilité blockchain (UUID + hash on-chain, RGPD).</p>
</body>
</html>
