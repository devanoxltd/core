@aware ([
    'timeout' => 5000,
    'toast' => new Illuminate\View\ComponentSlot,
    'close' => new Illuminate\View\ComponentSlot,
    'progressBarAlignment' => 'bottom',
    'progressBarVariant' => 'full',
])

<li
    :id="toast.id"
    x-data="{
    toastHovered: false,
    timeout: @js($timeout),
    toastId: null,
    el: null,
    progress: 100,
    startTime: null,
    animationId: null,
    totalPausedTime: 0,
    pauseStartTime: null,

    /**
     * Starts the requestAnimationFrame-based progress countdown.
     * Called once the toast entrance animation completes.
     */
    startTimer() {
        if (this.timeout <= 0) return;
        this.startTime = performance.now();
        this.animationId = requestAnimationFrame(() => this.updateProgress());
    },

    /**
     * Updates the progress bar width on each animation frame.
     * Handles pausing when this toast (or any above it) is hovered,
     * and triggers dismissal once the full duration has elapsed.
     */
    updateProgress() {
        const now = performance.now();
        const isPaused = $data.isToastPaused(this.toastId);

        if (isPaused && !this.pauseStartTime) {
            this.pauseStartTime = now;
        } else if (!isPaused && this.pauseStartTime) {
            this.totalPausedTime += now - this.pauseStartTime;
            this.pauseStartTime = null;
        }

        if (isPaused) {
            this.animationId = requestAnimationFrame(() => this.updateProgress());
            return;
        }

        const elapsed = now - this.startTime - this.totalPausedTime;
        this.progress = Math.max(0, 100 - (elapsed / this.timeout) * 100);

        if (elapsed >= this.timeout) {
            this.dismissToast();
            return;
        }

        this.animationId = requestAnimationFrame(() => this.updateProgress());
    },

    /**
     * Animates the toast out and removes it from the list.
     * Replicates the same exit animation used by burnToast().
     */
    dismissToast() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }

        this.el.firstElementChild.classList.remove('opacity-100');
        this.el.firstElementChild.classList.add('opacity-0');

        if (toasts.length === 1) {
            this.el.firstElementChild.classList.remove('translate-y-0');
            this.el.firstElementChild.classList.add('-translate-y-full');
        }

        setTimeout(() => {
            deleteToastWithId(this.toastId);
        }, 300);
    },

    /**
     * Cancels any pending animation frame when the component is destroyed.
     */
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
    },
}"
    x-init="
        $data.toastId = toast.id;
        $data.el = $el;
        if (position.includes('bottom')) {
            $el.firstElementChild.classList.add('toast-bottom');
            $el.firstElementChild.classList.add('opacity-0', 'translate-y-full');
        } else {
            $el.firstElementChild.classList.add('opacity-0', '-translate-y-full');
        }
        setTimeout(function () {
            setTimeout(function () {
                if (position.includes('bottom')) {
                    $el.firstElementChild.classList.remove('opacity-0', 'translate-y-full');
                } else {
                    $el.firstElementChild.classList.remove('opacity-0', '-translate-y-full');
                }
                $el.firstElementChild.classList.add('opacity-100', 'translate-y-0');

                setTimeout(function () {
                    stackToasts();
                }, 10);
                $data.startTimer();
            }, 5);
        }, 50);
    "
    @mouseover="
        toastHovered = true;
        pauseFromToast(toast.id);
    "
    @mouseout="
        toastHovered = false;
        resumeAllToasts();
    "
    {{ $toast->attributes->sanitize()->tailwindClass('absolute w-full select-none duration-300 ease-out sm:max-w-xs') }}
>
    <span
        {{ $attributes->sanitize()->tailwindClass('group relative flex w-full flex-col items-start overflow-hidden border border-gray-100 bg-white shadow-md transition-all duration-300 ease-out dark:border-gray-700 dark:bg-gray-800 sm:max-w-xs sm:rounded-md') }}
        :class="{ 'p-4': !toast.html, 'p-0': toast.html }"
    >
        <template x-if="!toast.html">
            <div class="relative">
                <div
                    class="flex items-center"
                    :class="{
                        'text-green-500': toast.type == 'success',
                        'text-blue-500': toast.type == 'info',
                        'text-orange-400': toast.type == 'warning',
                        'text-red-500': toast.type == 'error',
                        'text-gray-800': toast.type == 'default',
                    }"
                >
                    <x-ui.icon
                        x-show="toast.type == 'success'"
                        name="solid.check_circle"
                        class="mr-1.5 -ml-1 h-5 w-5"
                    />
                    <x-ui.icon x-show="toast.type == 'info'" name="solid.info" class="mr-1.5 -ml-1 h-5 w-5" />
                    <x-ui.icon x-show="toast.type == 'warning'" name="round.warning" class="mr-1.5 -ml-1 h-5 w-5" />
                    <x-ui.icon x-show="toast.type == 'error'" name="solid.circle-alert" class="mr-1.5 -ml-1 h-5 w-5" />
                    <p
                        class="text-sm leading-none font-medium text-gray-800 dark:text-neutral-100"
                        x-text="toast.message"
                    ></p>
                </div>
                <p
                    x-show="toast.description"
                    :class="{ 'pl-5': toast.type != 'default' }"
                    class="mt-1.5 text-xs leading-none opacity-70"
                    x-text="toast.description"
                ></p>
            </div>
        </template>
        <template x-if="toast.html">
            <div x-html="toast.html"></div>
        </template>
        <span
            @click="burnToast(toast.id)"
            {{ $close->attributes->sanitize()->tailwindClass('absolute z-10 right-0 mr-2.5 cursor-pointer rounded-full p-1.5 text-gray-400 opacity-0 duration-100 ease-in-out hover:bg-gray-50 hover:text-gray-500 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-gray-300') }}
            :class="{
                'top-1/2 -translate-y-1/2': !toast.description && !toast.html,
                'top-0 mt-2.5': toast.description || toast.html,
                'opacity-100': toastHovered,
                'opacity-0': !toastHovered,
            }"
        >
            <x-ui.icon name="outline.x-close" class="h-3 w-3" />
        </span>

        {{-- Progress bar: visually indicates remaining time before the toast auto-dismisses --}}
        <template x-if="toast.showProgress !== false && timeout > 0">
            <div
                @class ([
                    'absolute left-0 right-0 overflow-hidden',
                    'bottom-0' => $progressBarAlignment === 'bottom',
                    'top-0' => $progressBarAlignment === 'top',
                    'h-full' => $progressBarVariant === 'full',
                ])
                :class="{
                    'text-green-500': toast.type === 'success',
                    'text-blue-500': toast.type === 'info',
                    'text-orange-400': toast.type === 'warning',
                    'text-red-500': toast.type === 'error',
                    'text-gray-800 dark:text-gray-200': toast.type === 'default',
                }"
            >
                <div
                    @class ([
                        $progressBarVariant === 'full' ? 'h-full bg-current/10' : 'h-0.5 bg-current',
                    ])
                    :style="'width: ' + progress + '%'"
                ></div>
            </div>
        </template>
    </span>
</li>
