@extends('layouts.app')

@section('titre', $ticket->reference)

@section('styles')
    @include('tickets.partials.styles')
@endsection

@section('contenu')

    @php
        $peutGererTicket = auth()->user()->hasPermission('tickets.assign')
            || auth()->user()->est_technicien
            || auth()->user()->hasRole('admin');
        $estTechnicien = auth()->user()->est_technicien || auth()->user()->hasRole('admin');
        $estProprietaire = auth()->id() === $ticket->user_id;
        $actionsHistorique = [
            'creation' => 'Création du ticket',
            'changement_statut' => 'Changement de statut',
            'affectation' => 'Réaffectation',
            'commentaire' => 'Commentaire ajouté',
            'annulation' => 'Ticket annulé',
        ];
    @endphp

    <div class="page-actions">
        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            <span class="ref" style="font-size:1rem;">{{ $ticket->reference }}</span>
            @if ($ticket->statut)
                <span class="badge" style="background:{{ $ticket->statut->couleur }}22; color:{{ $ticket->statut->couleur }}; border:1px solid {{ $ticket->statut->couleur }}44;">
                    {{ $ticket->statut->nom }}
                </span>
            @endif
            @if ($ticket->priorite)
                <span class="badge" style="background:{{ $ticket->priorite->couleur }}22; color:{{ $ticket->priorite->couleur }}; border:1px solid {{ $ticket->priorite->couleur }}44;">
                    {{ $ticket->priorite->nom }}
                </span>
            @endif
            @if ($ticket->sla_depasse || $ticket->estEnRetard())
                <span class="badge badge-sla">SLA dépassé</span>
            @endif
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <a href="{{ route('tickets.index') }}" class="btn btn-secondaire btn-sm">← Liste</a>
            @if ($peutGererTicket || $estProprietaire)
                <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-secondaire btn-sm">Modifier</a>
            @endif
            @if ($estProprietaire || auth()->user()->hasRole('admin'))
                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Annuler ce ticket ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Annuler</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grille-2">
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            <div class="carte">
                <h2 class="section-titre">{{ $ticket->titre }}</h2>
                <div class="description">{{ $ticket->description }}</div>
            </div>

            <div class="carte">
                <h3 class="section-titre">Commentaires ({{ $ticket->comments->count() }})</h3>

                @foreach ($ticket->comments as $commentaire)
                    @if ($commentaire->interne && ! $estTechnicien)
                        @continue
                    @endif
                    <div class="commentaire {{ $commentaire->interne ? 'commentaire-interne' : '' }}">
                        <div class="commentaire-entete">
                            <span class="commentaire-auteur">
                                {{ $commentaire->auteur?->name ?? 'Utilisateur' }}
                                @if ($commentaire->interne)
                                    <span class="badge" style="background:rgba(200,150,62,0.15); color:var(--ambre-clair); margin-left:0.35rem;">Interne</span>
                                @endif
                            </span>
                            <span class="commentaire-date">{{ $commentaire->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="description" style="font-size:0.85rem;">{{ $commentaire->contenu }}</div>
                    </div>
                @endforeach

                @if ($ticket->comments->isEmpty())
                    <p style="color:var(--texte-att); font-size:0.85rem; margin:0;">Aucun commentaire pour l'instant.</p>
                @endif

                <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" style="margin-top:1.25rem; padding-top:1.25rem; border-top:1px solid var(--graphite-line);">
                    @csrf
                    <div class="champ" style="margin-bottom:0.75rem;">
                        <label for="contenu">Ajouter un commentaire</label>
                        <textarea id="contenu" name="contenu" required placeholder="Votre message…"></textarea>
                    </div>
                    @if ($estTechnicien)
                        <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.82rem; color:var(--texte-att); margin-bottom:0.75rem; cursor:pointer;">
                            <input type="checkbox" name="interne" value="1" style="accent-color:var(--ambre);">
                            Commentaire interne (visible uniquement par les techniciens)
                        </label>
                    @endif
                    <button type="submit" class="btn btn-primaire btn-sm">Publier</button>
                </form>
            </div>

            <div class="carte">
                <h3 class="section-titre">Historique</h3>
                @forelse ($ticket->histories as $historique)
                    <div class="historique-item">
                        <span class="historique-point"></span>
                        <div>
                            <strong>{{ $actionsHistorique[$historique->action] ?? $historique->action }}</strong>
                            @if ($historique->action === 'changement_statut')
                                — {{ $statutsMap[$historique->ancienne_valeur] ?? '?' }} → {{ $statutsMap[$historique->nouvelle_valeur] ?? '?' }}
                            @elseif ($historique->action === 'affectation')
                                — {{ $techniciensMap[$historique->ancienne_valeur] ?? 'Non assigné' }} → {{ $techniciensMap[$historique->nouvelle_valeur] ?? 'Non assigné' }}
                            @elseif ($historique->action === 'creation')
                                — {{ $historique->nouvelle_valeur }}
                            @endif
                            <div style="color:var(--texte-att); font-size:0.78rem; margin-top:0.15rem;">
                                {{ $historique->utilisateur?->name ?? 'Système' }} · {{ $historique->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="color:var(--texte-att); font-size:0.85rem; margin:0;">Aucun historique.</p>
                @endforelse
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            <div class="carte">
                <h3 class="section-titre">Informations</h3>
                <ul class="meta-liste">
                    <li><span class="label">Demandeur</span><span>{{ $ticket->demandeur?->name ?? '—' }}</span></li>
                    <li><span class="label">Catégorie</span><span>{{ $ticket->categorie?->nom ?? '—' }}</span></li>
                    <li><span class="label">Site</span><span>{{ $ticket->site?->nom ?? '—' }}</span></li>
                    <li><span class="label">Département</span><span>{{ $ticket->departement?->nom ?? '—' }}</span></li>
                    <li><span class="label">Impact</span><span>{{ $ticket->impact }}</span></li>
                    <li><span class="label">Urgence</span><span>{{ $ticket->urgence }}</span></li>
                    <li><span class="label">Équipe</span><span>{{ $ticket->team?->nom ?? '—' }}</span></li>
                    <li><span class="label">Technicien</span><span>{{ $ticket->technicien?->name ?? 'Non assigné' }}</span></li>
                    <li><span class="label">Créé le</span><span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span></li>
                    @if ($ticket->date_echeance_resolution)
                        <li><span class="label">Échéance SLA</span><span>{{ $ticket->date_echeance_resolution->format('d/m/Y H:i') }}</span></li>
                    @endif
                </ul>
            </div>

            <div class="carte">
                <h3 class="section-titre">Pièces jointes ({{ $ticket->attachments->count() }})</h3>
                @if ($ticket->attachments->isNotEmpty())
                    <ul class="pj-liste">
                        @foreach ($ticket->attachments as $pj)
                            <li>
                                <span>{{ $pj->nom_original }}</span>
                                <a href="{{ Storage::disk('public')->url($pj->chemin) }}" target="_blank" rel="noopener">Télécharger</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="color:var(--texte-att); font-size:0.85rem; margin:0 0 1rem;">Aucune pièce jointe.</p>
                @endif

                <form method="POST" action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data" style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--graphite-line);">
                    @csrf
                    <div class="champ" style="margin-bottom:0.75rem;">
                        <input type="file" name="fichier" required accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
                    </div>
                    <button type="submit" class="btn btn-secondaire btn-sm">Joindre un fichier</button>
                </form>
            </div>
        </div>
    </div>

@endsection
