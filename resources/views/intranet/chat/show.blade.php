<x-intranet-layout title="Chat · {{ $room->name }}">
    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        <aside class="space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-graphite-900">Chat</h1>
                @if(auth()->user()->is_super_admin)
                    <a href="{{ route('intranet.chat.rooms.create') }}" class="text-sm font-medium text-nere-700 hover:underline">+ Salon privé</a>
                @endif
            </div>
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @foreach($groups as $label => $rooms)
                @continue($rooms->isEmpty())
                <nav aria-label="{{ $label }}">
                    <h2 class="mb-1 text-xs font-semibold uppercase tracking-wider text-graphite-500">{{ $label }}</h2>
                    <ul class="space-y-0.5">
                        @foreach($rooms as $item)
                            <li>
                                <a href="{{ route('intranet.chat.show', $item) }}" @if($item->is($room)) aria-current="page" @endif
                                   class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm {{ $item->is($room) ? 'bg-nere-50 font-semibold text-nere-800' : 'text-graphite-700 hover:bg-graphite-50' }}">
                                    <span class="truncate">@if($item->type === \App\Models\ChatRoom::PRIVATE)<svg class="mr-1 inline h-3.5 w-3.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="Privé"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@else# @endif{{ $item->name }}</span>
                                    @if(! $item->is($room) && ($unread[$item->id] ?? 0) > 0)
                                        <span class="rounded-full bg-nere-600 px-2 py-0.5 text-xs font-semibold text-white">{{ $unread[$item->id] > 99 ? '99+' : $unread[$item->id] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </aside>

        <section class="flex h-[calc(100vh-11rem)] min-h-[28rem] flex-col rounded-xl border border-graphite-200 bg-white" x-data="chatRoom(@js($config))">
            <header class="flex items-center justify-between gap-3 border-b border-graphite-100 px-5 py-3">
                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-graphite-900">@if($room->type === \App\Models\ChatRoom::PRIVATE)<svg class="mr-1 inline h-3.5 w-3.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="Privé"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>@else# @endif{{ $room->name }}</h2>
                    <p class="text-xs text-graphite-500">
                        @switch($room->type)
                            @case(\App\Models\ChatRoom::GENERAL) Tous les collaborateurs @break
                            @case(\App\Models\ChatRoom::DEPARTMENT) Membres du département @break
                            @default Salon privé · {{ $room->members()->count() }} membre(s)
                        @endswitch
                        · messages conservés {{ \App\Models\ChatRoom::RETENTION_MONTHS }} mois
                    </p>
                </div>
                @if($room->type === \App\Models\ChatRoom::PRIVATE && auth()->user()->is_super_admin)
                    <a href="{{ route('intranet.chat.rooms.edit', $room) }}" class="shrink-0 text-sm font-medium text-nere-700 hover:underline">Gérer le salon</a>
                @endif
            </header>

            <div x-ref="list" class="flex-1 space-y-1 overflow-y-auto px-5 py-4" aria-live="polite">
                <template x-if="messages.length === 0">
                    <p class="py-10 text-center text-sm text-graphite-500">Aucun message pour l'instant. Lancez la discussion !</p>
                </template>
                <template x-for="(message, index) in messages" :key="message.id">
                    <div>
                        <template x-if="showDate(index)">
                            <p class="my-3 text-center text-xs font-medium text-graphite-400" x-text="message.date"></p>
                        </template>
                        <div class="group flex gap-3 rounded-md px-2 py-1.5 hover:bg-graphite-50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="message.mine ? 'bg-nere-100 text-nere-800' : 'bg-graphite-100 text-graphite-700'" x-text="message.initials"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm">
                                    <span class="font-semibold text-graphite-900" x-text="message.author"></span>
                                    <span class="ml-1 text-xs text-graphite-400" x-text="message.time"></span>
                                    <span x-show="message.edited && !message.deleted" class="ml-1 text-xs text-graphite-400">(modifié)</span>
                                </p>
                                <template x-if="message.deleted">
                                    <p class="text-sm italic text-graphite-400">Message supprimé</p>
                                </template>
                                <template x-if="!message.deleted && editingId !== message.id">
                                    <p class="whitespace-pre-wrap break-words text-sm text-graphite-800"><template x-for="(part, i) in segments(message.body)" :key="i"><span><template x-if="part.url"><a :href="part.text" target="_blank" rel="noopener noreferrer" class="text-nere-700 underline" x-text="part.text"></a></template><template x-if="!part.url"><span x-text="part.text"></span></template></span></template></p>
                                </template>
                                <template x-if="editingId === message.id">
                                    <div class="mt-1 space-y-2">
                                        <textarea x-model="editBody" rows="2" maxlength="2000" class="champ text-sm" @keydown.enter.prevent="if (!$event.shiftKey) saveEdit(message)" @keydown.escape="editingId = null"></textarea>
                                        <div class="flex gap-2 text-xs">
                                            <button type="button" @click="saveEdit(message)" class="font-semibold text-nere-700 hover:underline">Enregistrer</button>
                                            <button type="button" @click="editingId = null" class="text-graphite-600 hover:underline">Annuler</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex shrink-0 items-start gap-2 text-xs opacity-0 transition group-hover:opacity-100 focus-within:opacity-100" x-show="editingId !== message.id">
                                <button type="button" x-show="message.can_edit" @click="startEdit(message)" class="text-graphite-500 hover:text-nere-700">Modifier</button>
                                <button type="button" x-show="message.can_delete" @click="remove(message)" class="text-graphite-500 hover:text-red-700">Supprimer</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="send()" class="border-t border-graphite-100 p-3">
                <p x-show="error" x-text="error" class="mb-2 text-sm text-red-700" role="alert"></p>
                <div class="flex items-end gap-2">
                    <label for="chat-body" class="sr-only">Votre message</label>
                    <textarea id="chat-body" x-model="body" @keydown="onKeydown($event)" rows="2" maxlength="2000" placeholder="Écrire un message… (Entrée pour envoyer, Maj+Entrée pour aller à la ligne)" class="champ flex-1 resize-none text-sm"></textarea>
                    <button class="bouton-primaire shrink-0" :disabled="sending || !body.trim()">Envoyer</button>
                </div>
            </form>
        </section>
    </div>
</x-intranet-layout>
