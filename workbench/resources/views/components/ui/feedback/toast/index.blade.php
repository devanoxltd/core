@props ([
    'layout' => 'default', // default, expanded
    'position' => 'bottom-right', // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
    'timeout' => 5000,
    'maxToasts' => 5,
    'bodyWrapper' => new Illuminate\View\ComponentSlot,
    'toasts' => new Illuminate\View\ComponentSlot,
    'toast' => new Illuminate\View\ComponentSlot,
    'close' => new Illuminate\View\ComponentSlot,
    'progressBarVariant' => 'full',
    'progressBarAlignment' => 'bottom'
])
<div
    x-data
    x-init="
        window.toast = function (message, options = {}) {
            let description = '';
            let type = 'default';
            let html = '';

            if (typeof options.description != 'undefined') description = options.description;
            if (typeof options.type != 'undefined') type = options.type;
            if (typeof options.html != 'undefined') html = options.html;

            window.dispatchEvent(
                new CustomEvent('toast', {
                    detail: {
                        type: type,
                        message: message,
                        description: description,
                        html: html,
                    },
                }),
            );
        }
    "
    {{ $bodyWrapper->attributes->sanitize()->tailwindClass('hidden') }}
>
    <template x-teleport="body">
        <ul
            x-data="{
                toasts: [],
                maxToasts: @js($maxToasts),
                pausedToastIds: new Set(),
                toastsHovered: false,
                expanded: false,
                layout: @js($layout),
                position: @js($position),
                audio: new Audio(@js(asset('sounds/notification.mp3'))),
                paddingBetweenToasts: 15,
                addToast(toast) {
                    if (!toast?.message && !toast?.html) return

                    if (toast?.position) {
                        this.position = toast.position
                    }

                    this.playSound();

                    this.toasts.unshift({
                        id: 'toast-' + Math.random().toString(16).slice(2),
                        show: false,
                        message: toast.message,
                        description: toast.description || '',
                        type: toast.type || 'default',
                        html: toast.html || '',
                        showProgress: toast?.showProgress !== false, // default true
                    });

                    // Limit number of toasts shown at once
                    if (this.toasts.length > this.maxToasts) {
                        this.toasts = this.toasts.slice(0, this.maxToasts)
                    }
                },
                deleteToastWithId(id) {
                    this.toasts = this.toasts.filter(toast => toast.id !== id)
                    this.pausedToastIds.delete(id)
                },
                playSound() {
                    this.audio.currentTime = 0 // from start
                    let playPromise = this.audio.play()
                    if (playPromise !== undefined) {
                        playPromise.catch(error => {
                            // Autoplay was prevented by the browser
                        })
                    }
                },
                burnToast(id) {
                    burnToast = this.getToastWithId(id)
                    burnToastElement = document.getElementById(burnToast.id)
                    if (burnToastElement) {
                        if (this.toasts.length == 1) {
                            if (this.layout == 'default') {
                                this.expanded = false
                            }
                            burnToastElement.classList.remove('translate-y-0')
                            if (this.position.includes('bottom')) {
                                burnToastElement.classList.add('translate-y-full')
                            } else {
                                burnToastElement.classList.add('-translate-y-full')
                            }
                            burnToastElement.classList.add('-translate-y-full')
                        }
                        burnToastElement.classList.add('opacity-0')
                        let that = this
                        setTimeout(function () {
                            that.deleteToastWithId(id)
                            setTimeout(function () {
                                that.stackToasts()
                            }, 1)
                        }, 300)
                    }
                },
                getToastWithId(id) {
                    for (let i = 0; i < this.toasts.length; i++) {
                        if (this.toasts[i].id === id) {
                            return this.toasts[i]
                        }
                    }
                },
                stackToasts() {
                    this.positionToasts()
                    this.calculateHeightOfToastsContainer()
                    let that = this
                    setTimeout(function () {
                        that.calculateHeightOfToastsContainer()
                    }, 300)
                },
                positionToasts() {
                    if (this.toasts.length == 0) return

                    // Slot 0 — most-recent toast, always fully visible
                    let topToast = document.getElementById(this.toasts[0].id)
                    topToast.style.zIndex = 100
                    topToast.firstElementChild.classList.remove('opacity-0')
                    topToast.firstElementChild.classList.add('opacity-100')
                    if (this.expanded) {
                        if (this.position.includes('bottom')) {
                            topToast.style.top = 'auto'
                            topToast.style.bottom = '0px'
                        } else {
                            topToast.style.top = '0px'
                        }
                    }

                    let bottomPositionOfFirstToast =
                        this.getBottomPositionOfElement(topToast)

                    if (this.toasts.length == 1) return

                    // Slot 1
                    let middleToast = document.getElementById(this.toasts[1].id)
                    middleToast.style.zIndex = 90
                    middleToast.firstElementChild.classList.remove('opacity-0')
                    middleToast.firstElementChild.classList.add('opacity-100')

                    if (this.expanded) {
                        middleToastPosition =
                            topToast.getBoundingClientRect().height +
                            this.paddingBetweenToasts +
                            'px'

                        if (this.position.includes('bottom')) {
                            middleToast.style.top = 'auto'
                            middleToast.style.bottom = middleToastPosition
                        } else {
                            middleToast.style.top = middleToastPosition
                        }

                        middleToast.style.scale = '100%'
                        middleToast.style.transform = 'translateY(0px)'
                    } else {
                        middleToast.style.scale = '94%'
                        if (this.position.includes('bottom')) {
                            middleToast.style.transform = 'translateY(-16px)'
                        } else {
                            this.alignBottom(topToast, middleToast)
                            middleToast.style.transform = 'translateY(16px)'
                        }
                    }

                    if (this.toasts.length == 2) return

                    // Slot 2
                    let bottomToast = document.getElementById(this.toasts[2].id)
                    bottomToast.style.zIndex = 80
                    bottomToast.firstElementChild.classList.remove('opacity-0')
                    bottomToast.firstElementChild.classList.add('opacity-100')

                    if (this.expanded) {
                        bottomToastPosition =
                            topToast.getBoundingClientRect().height +
                            this.paddingBetweenToasts +
                            middleToast.getBoundingClientRect().height +
                            this.paddingBetweenToasts +
                            'px'

                        if (this.position.includes('bottom')) {
                            bottomToast.style.top = 'auto'
                            bottomToast.style.bottom = bottomToastPosition
                        } else {
                            bottomToast.style.top = bottomToastPosition
                        }

                        bottomToast.style.scale = '100%'
                        bottomToast.style.transform = 'translateY(0px)'
                    } else {
                        bottomToast.style.scale = '88%'
                        if (this.position.includes('bottom')) {
                            bottomToast.style.transform = 'translateY(-32px)'
                        } else {
                            this.alignBottom(topToast, bottomToast)
                            bottomToast.style.transform = 'translateY(32px)'
                        }
                    }

                    if (this.toasts.length == 3) return

                    // Slots 3+ — queue toasts that exceed the 3-visible stacking limit.
                    // In stacked (default) mode they are hidden but kept in the array so
                    // they slide into a visible slot when a higher-priority toast is dismissed.
                    // In expanded mode every queued toast is fully positioned and visible.
                    for (let i = 3; i < this.toasts.length; i++) {
                        let extraToast = document.getElementById(this.toasts[i].id)
                        if (!extraToast) continue

                        extraToast.style.zIndex = Math.max(10, 70 - ((i - 3) * 10))

                        if (this.expanded) {
                            let offset = 0
                            for (let j = 0; j < i; j++) {
                                let prevEl = document.getElementById(this.toasts[j].id)
                                if (prevEl) {
                                    offset += prevEl.getBoundingClientRect().height + this.paddingBetweenToasts
                                }
                            }
                            if (this.position.includes('bottom')) {
                                extraToast.style.top = 'auto'
                                extraToast.style.bottom = offset + 'px'
                            } else {
                                extraToast.style.top = offset + 'px'
                            }
                            extraToast.style.scale = '100%'
                            extraToast.style.transform = 'translateY(0px)'
                            extraToast.firstElementChild.classList.remove('opacity-0')
                            extraToast.firstElementChild.classList.add('opacity-100')
                        } else {
                            extraToast.firstElementChild.classList.remove('opacity-100')
                            extraToast.firstElementChild.classList.add('opacity-0')
                        }
                    }

                    if (this.position.includes('bottom')) {
                        middleToast.style.top = 'auto'
                    }

                    return
                },
                alignBottom(element1, element2) {
                    // Get the top position and height of the first element
                    let top1 = element1.offsetTop
                    let height1 = element1.offsetHeight

                    // Get the height of the second element
                    let height2 = element2.offsetHeight

                    // Calculate the top position for the second element
                    let top2 = top1 + (height1 - height2)

                    // Apply the calculated top position to the second element
                    element2.style.top = top2 + 'px'
                },
                alignTop(element1, element2) {
                    // Get the top position of the first element
                    let top1 = element1.offsetTop

                    // Apply the same top position to the second element
                    element2.style.top = top1 + 'px'
                },
                resetBottom() {
                    for (let i = 0; i < this.toasts.length; i++) {
                        if (document.getElementById(this.toasts[i].id)) {
                            let toastElement = document.getElementById(this.toasts[i].id)
                            toastElement.style.bottom = '0px'
                        }
                    }
                },
                resetTop() {
                    for (let i = 0; i < this.toasts.length; i++) {
                        if (document.getElementById(this.toasts[i].id)) {
                            let toastElement = document.getElementById(this.toasts[i].id)
                            toastElement.style.top = '0px'
                        }
                    }
                },
                getBottomPositionOfElement(el) {
                    return (
                        el.getBoundingClientRect().height + el.getBoundingClientRect().top
                    )
                },
                calculateHeightOfToastsContainer() {
                    if (this.toasts.length == 0) {
                        $el.style.height = '0px'
                        return
                    }

                    lastToast = this.toasts[this.toasts.length - 1]
                    lastToastRectangle = document
                        .getElementById(lastToast.id)
                        .getBoundingClientRect()

                    firstToast = this.toasts[0]
                    firstToastRectangle = document
                        .getElementById(firstToast.id)
                        .getBoundingClientRect()

                    if (this.toastsHovered) {
                        if (this.position.includes('bottom')) {
                            $el.style.height =
                                firstToastRectangle.top +
                                firstToastRectangle.height -
                                lastToastRectangle.top +
                                'px'
                        } else {
                            $el.style.height =
                                lastToastRectangle.top +
                                lastToastRectangle.height -
                                firstToastRectangle.top +
                                'px'
                        }
                    } else {
                        $el.style.height = firstToastRectangle.height + 'px'
                    }
                },
                showSessionToast() {
                    // use for toasts used after redirects, any backend toast can be set in the session with the key 'toast' and it will be shown on the next page load, after being pulled from the session. The session toast should be an object with at least a 'message' property, and optionally 'description', 'type', 'position', and 'html' properties.
                    let session = {!! \Illuminate\Support\Js::from(session()->pull('toast')) !!}

                    if (!session || (!session?.message)) {
                        return
                    }

                    // setTimeout is used to ensure that the toast component is fully initialized before trying to show the toast.
                    setTimeout(function () {
                        window.toast(session.message, {
                            description: session?.description ?? '',
                            type: session?.type ?? 'default',
                            position: session?.position ?? this.position,
                            html: session?.html ?? '',
                        })
                    }, 100)

                },
                pauseFromToast(targetId) {
                    const targetIndex = this.toasts.findIndex(toast => toast.id === targetId);

                    if (targetIndex === -1) return;

                    // Pause the target toast and all toasts above it (index 0 to targetIndex)
                    this.pausedToastIds.clear();

                    for (let i = 0; i <= targetIndex; i++) {
                        this.pausedToastIds.add(this.toasts[i].id);
                    }
                },
                resumeAllToasts() {
                    this.pausedToastIds.clear();
                },
                isToastPaused(id) {
                    return this.pausedToastIds.has(id);
                },
            }"
            @set-toasts-layout.window="
                layout = event.detail.layout;
                if (layout == 'expanded') {
                    expanded = true;
                } else {
                    expanded = false;
                }
                stackToasts();
            "
            @toast.window="
                event.stopPropagation();
                addToast(event.detail);
            "
            @mouseenter="toastsHovered = true"
            @mouseleave="toastsHovered = false"
            x-init="
                if (layout == 'expanded') {
                    expanded = true;
                }
                stackToasts();
                $watch('toastsHovered', function (value) {
                    if (layout == 'default') {
                        if (position.includes('bottom')) {
                            resetBottom();
                        } else {
                            resetTop();
                        }

                        if (value) {
                            // calculate the new positions
                            expanded = true;
                            if (layout == 'default') {
                                stackToasts();
                            }
                        } else {
                            if (layout == 'default') {
                                expanded = false;
                                //setTimeout(function(){
                                stackToasts();
                                //}, 10);
                                setTimeout(function () {
                                    stackToasts();
                                }, 10);
                            }
                        }
                    }
                });
                showSessionToast();
            "
            :class="{
                'right-0 top-0 sm:mt-6 sm:mr-6': position == 'top-right',
                'left-0 top-0 sm:mt-6 sm:ml-6': position == 'top-left',
                'left-1/2 -translate-x-1/2 top-0 sm:mt-6': position == 'top-center',
                'right-0 bottom-0 sm:mr-6 sm:mb-6': position == 'bottom-right',
                'left-0 bottom-0 sm:ml-6 sm:mb-6': position == 'bottom-left',
                'left-1/2 -translate-x-1/2 bottom-0 sm:mb-6': position == 'bottom-center',
            }"
            x-cloak
            {{ $toasts->attributes->sanitize()->tailwindClass('group fixed z-99 block w-full sm:max-w-xs') }}
        >
            <template x-for="(toast, index) in toasts" :key="toast.id">
                <x-ui.feedback.toast.item />
            </template>
        </ul>
    </template>
</div>
