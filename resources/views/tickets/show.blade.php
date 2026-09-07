@extends('layouts.app')

@section('titre', $ticket->reference)

@section('styles')
    @include('tickets.partials.styles')
@endsection

@section('contenu')

    @php
        $estTechnicien = auth()->user()->est_technicien || auth()->user()->hasRole('admin');
        $peutValider = $estTechnicien || auth()->user()->hasPermission('tickets.assign');
        $estProprietaire = auth()->id() === $ticket->user_id;
        $actionsHistorique = [
            'creation' => 'Création du ticket',
            'changement_statut' => 'Changement de statut',
            'affectation' => 'Réaffectation',
            'commentaire' => 'Commentaire ajouté',
            'annulation' => 'Ticket annulé',
        ];
    @endphp

    @php($peutModifierTicket = $estProprietaire || auth()->user()->hasRole('admin'))

    @php($actionsDisponibles = app(\App\Services\TicketWorkflowService::class)->actionsDisponibles($ticket, auth()->user()))

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
            @if ($ticket->validation_statut === 'en_attente')
                <span class="badge" style="background:rgba(224,165,47,.15);color:var(--ambre-clair);">Validation requise</span>
            @elseif ($ticket->validation_statut === 'valide')
                <span class="badge" style="background:rgba(63,166,107,.12);color:#7fd4a0;">Validé</span>
            @elseif ($ticket->validation_statut === 'refuse')
                <span class="badge" style="background:rgba(214,69,69,.12);color:#f0a0a0;">Refusé</span>
            @endif
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <a href="{{ route('tickets.index') }}" class="btn btn-secondaire btn-sm">← Liste</a>
            @foreach ($actionsDisponibles as $slug => $action)
                <form method="POST" action="{{ route('tickets.transition', $ticket) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="statut" value="{{ $slug }}">
                    @if ($slug === 'assigne' && ! $ticket->assigned_to)
                        @if (auth()->user()->hasRole('dsi') || auth()->user()->hasRole('admin'))
                            <label for="assigned_to" style="position:absolute; width:1px; height:1px; overflow:hidden;">Technicien à affecter</label>
                            <select id="assigned_to" name="assigned_to" required style="max-width:220px; padding:.4rem .55rem; border:1px solid var(--graphite-line); border-radius:8px; background:var(--graphite-soft); color:var(--texte-clair);">
                                <option value="">Choisir un technicien</option>
                                @foreach ($techniciens as $technicien)
                                    <option value="{{ $technicien->id }}">{{ $technicien->name }}{{ $technicien->disponible ? '' : ' · indisponible' }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                        @endif
                    @elseif ($slug === 'en_attente')
                        <input type="hidden" name="motif_attente" value="Mise en attente depuis la fiche ticket">
                    @elseif ($slug === 'resolu')
                        <input type="hidden" name="solution" value="Résolution renseignée depuis la fiche ticket">
                    @endif
                    <button type="submit" class="btn btn-{{ $action['style'] }} btn-sm">{{ $action['label'] }}</button>
                </form>
            @endforeach
            @if ($peutModifierTicket)
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

    @if ($ticket->validation_statut === 'en_attente' && $peutValider)
        <div class="carte" style="margin-bottom:1.25rem;border-color:rgba(224,165,47,.4);">
            <h3 class="section-titre">Validation du service</h3>
            <p style="color:var(--texte-att);font-size:.85rem;">Cette demande RH, Finance ou HSE doit être validée par un responsable du service.</p>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <form method="POST" action="{{ route('tickets.validate', $ticket) }}">@csrf<button class="btn btn-primaire btn-sm" type="submit">Valider la demande</button></form>
                <form method="POST" action="{{ route('tickets.reject', $ticket) }}" style="display:flex;gap:.5rem;align-items:center;"><input name="motif" required placeholder="Motif du refus"><button class="btn btn-danger btn-sm" type="submit">Refuser</button></form>
            </div>
        </div>
    @endif

    <div class="grille-2">
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            <div class="carte">
                <h2 class="section-titre">{{ $ticket->titre }}</h2>
                <div class="description">{{ $ticket->description }}</div>
                @if ($ticket->service_data)
                    <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--graphite-line);">
                        <h3 class="section-titre" style="font-size:.88rem;">Informations du service</h3>
                        <ul class="meta-liste">
                            @foreach (['date' => "Date de l'événement", 'heure' => 'Heure', 'lieu' => 'Lieu', 'gravite' => 'Gravité', 'personnes' => 'Personnes impliquées'] as $cle => $libelle)
                                @if (filled($ticket->service_data[$cle] ?? null))
                                    <li><span class="label">{{ $libelle }}</span><span>{{ $ticket->service_data[$cle] }}</span></li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
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
