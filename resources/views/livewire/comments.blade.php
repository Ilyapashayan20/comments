<div class="space-y-4"
    @if (!\Relaticle\Comments\CommentsConfig::isBroadcastingEnabled())
        wire:poll.{{ \Relaticle\Comments\CommentsConfig::getPollingInterval() }}
    @endif
>
    {{-- Sort toggle --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Comments ({{ $this->totalCount }})
        </h3>
        @auth
            <div class="flex items-center gap-3">
                <button wire:click="toggleSubscription" type="button"
                    class="flex items-center gap-1 text-xs {{ $this->isSubscribed ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }} hover:text-primary-500">
                    @if ($this->isSubscribed)
                        {{-- Bell icon (solid) --}}
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 00.515 1.076 32.91 32.91 0 003.256.508 3.5 3.5 0 006.972 0 32.903 32.903 0 003.256-.508.75.75 0 00.515-1.076A11.448 11.448 0 0116 8a6 6 0 00-6-6zm0 14.5a2 2 0 01-1.95-1.557 33.146 33.146 0 003.9 0A2 2 0 0110 16.5z" clip-rule="evenodd"/>
                        </svg>
                        Subscribed
                    @else
                        {{-- Bell icon (outline) --}}
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                        </svg>
                        Subscribe
                    @endif
                </button>
                <button wire:click="toggleSort" type="button"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    {{ $sortDirection === 'asc' ? 'Oldest first' : 'Newest first' }}
                </button>
            </div>
        @else
            <button wire:click="toggleSort" type="button"
                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                {{ $sortDirection === 'asc' ? 'Oldest first' : 'Newest first' }}
            </button>
        @endauth
    </div>

    {{-- Comment list --}}
    <div class="space-y-4">
        @foreach ($this->comments as $comment)
            <livewire:comment-item :comment="$comment" :key="'comment-'.$comment->id" />
        @endforeach
    </div>

    {{-- Load more button --}}
    @if ($this->hasMore)
        <div class="text-center">
            <button wire:click="loadMore" type="button"
                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Load more comments
                <span wire:loading wire:target="loadMore" class="ml-1">...</span>
            </button>
        </div>
    @endif

    {{-- New comment form - only for authorized users --}}
    @auth
        @can('create', \Relaticle\Comments\CommentsConfig::getCommentModel())
            <form wire:submit="addComment" class="relative mt-4"
                x-data="{
                    showMentions: false,
                    mentionQuery: '',
                    mentionResults: [],
                    selectedIndex: 0,
                    mentionStart: null,
                    async handleInput(event) {
                        const textarea = event.target;
                        const value = textarea.value;
                        const cursorPos = textarea.selectionStart;
                        const textBeforeCursor = value.substring(0, cursorPos);
                        const atIndex = textBeforeCursor.lastIndexOf('@');
                        if (atIndex !== -1 && (atIndex === 0 || textBeforeCursor[atIndex - 1] === ' ' || textBeforeCursor[atIndex - 1] === '\n')) {
                            const query = textBeforeCursor.substring(atIndex + 1);
                            if (query.length > 0 && !query.includes(' ')) {
                                this.mentionStart = atIndex;
                                this.mentionQuery = query;
                                this.mentionResults = await $wire.searchUsers(query);
                                this.showMentions = this.mentionResults.length > 0;
                                this.selectedIndex = 0;
                                return;
                            }
                        }
                        this.showMentions = false;
                    },
                    handleKeydown(event) {
                        if (!this.showMentions) return;
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            this.selectedIndex = Math.min(this.selectedIndex + 1, this.mentionResults.length - 1);
                        } else if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                        } else if (event.key === 'Enter' || event.key === 'Tab') {
                            if (this.mentionResults.length > 0) {
                                event.preventDefault();
                                this.selectMention(this.mentionResults[this.selectedIndex]);
                            }
                        } else if (event.key === 'Escape') {
                            this.showMentions = false;
                        }
                    },
                    selectMention(user) {
                        const textarea = this.$refs.commentInput;
                        const value = textarea.value;
                        const before = value.substring(0, this.mentionStart);
                        const after = value.substring(textarea.selectionStart);
                        const newValue = before + '@' + user.name + ' ' + after;
                        $wire.set('newComment', newValue);
                        this.showMentions = false;
                        this.$nextTick(() => {
                            const pos = before.length + user.name.length + 2;
                            textarea.focus();
                            textarea.setSelectionRange(pos, pos);
                        });
                    }
                }">
                <x-filament::input.wrapper>
                    <textarea
                        x-ref="commentInput"
                        wire:model="newComment"
                        @input="handleInput($event)"
                        @keydown="handleKeydown($event)"
                        rows="3"
                        placeholder="Write a comment..."
                        class="block w-full border-none bg-transparent px-3 py-1.5 text-sm leading-6 text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                    ></textarea>
                </x-filament::input.wrapper>

                {{-- Mention autocomplete dropdown --}}
                <div x-show="showMentions" x-cloak
                    class="absolute z-50 mt-1 w-64 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800">
                    <template x-for="(user, index) in mentionResults" :key="user.id">
                        <button type="button"
                            @click="selectMention(user)"
                            :class="{ 'bg-primary-50 dark:bg-primary-900/20': index === selectedIndex }"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                            <template x-if="user.avatar_url">
                                <img :src="user.avatar_url" class="h-6 w-6 rounded-full object-cover" />
                            </template>
                            <template x-if="!user.avatar_url">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700 dark:bg-primary-800 dark:text-primary-300"
                                    x-text="user.name.charAt(0).toUpperCase()"></div>
                            </template>
                            <span x-text="user.name" class="text-gray-900 dark:text-gray-100"></span>
                        </button>
                    </template>
                </div>

                @error('newComment')
                    <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror

                @if (\Relaticle\Comments\CommentsConfig::areAttachmentsEnabled())
                    <div class="mt-2">
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                            Attach files
                            <input type="file" wire:model="attachments" multiple class="hidden" accept="{{ implode(',', \Relaticle\Comments\CommentsConfig::getAttachmentAllowedTypes()) }}" />
                        </label>
                    </div>

                    @if (!empty($attachments))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($attachments as $index => $file)
                                <div class="flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <span>{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeAttachment({{ $index }})" class="text-gray-400 hover:text-danger-500 dark:text-gray-500 dark:hover:text-danger-400">&times;</button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('attachments.*')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                @endif

                <div class="mt-2 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:ring-offset-gray-800"
                        wire:loading.attr="disabled" wire:target="addComment">
                        <span wire:loading.remove wire:target="addComment">Comment</span>
                        <span wire:loading wire:target="addComment">Posting...</span>
                    </button>
                </div>
            </form>
        @endcan
    @endauth
</div>
