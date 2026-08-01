import * as bootstrap from 'bootstrap'
import $ from 'jquery'

window.bootstrap = bootstrap
window.publicBootstrap = bootstrap
window.$ = window.$ || $
window.jQuery = window.jQuery || $

const select2Module = await import('select2/dist/js/select2.full')

if (typeof $.fn.select2 !== 'function' && typeof select2Module.default === 'function') {
    select2Module.default(window, $)
}

const initPublicSite = () => {
    const header = document.querySelector('[data-public-header]')
    const revealItems = document.querySelectorAll('[data-reveal]')
    const newsletterForms = document.querySelectorAll('[data-newsletter-form]')
    const heroMedia = document.querySelector('[data-hero-media]')
    const heroLoader = document.querySelector('[data-hero-loader]')
    const heroVideo = document.querySelector('[data-hero-video]')
    const heroIframe = document.querySelector('[data-hero-iframe]')
    const heroVideoSource = heroVideo?.querySelector('source[data-src]')
    const announcementsModalElement = document.querySelector('[data-public-announcements-modal]')
    const roomGalleries = document.querySelectorAll('[data-public-room-gallery]')
    const countrySelects = document.querySelectorAll('[data-country-select]')
    const prefersMobileHeroImage = heroMedia?.dataset.mobileHeroImage === 'true'
    const mobileMediaQuery = window.matchMedia('(max-width: 767.98px)')
    const shouldSkipHeavyHeroMedia = prefersMobileHeroImage && mobileMediaQuery.matches

    if (heroVideoSource && !shouldSkipHeavyHeroMedia) {
        heroVideoSource.src = heroVideoSource.dataset.src
        heroVideo.load()
    }

    if (heroIframe && !shouldSkipHeavyHeroMedia && heroIframe.dataset.src) {
        heroIframe.src = heroIframe.dataset.src
    }

    const syncHeaderState = () => {
        if (!header) {
            return
        }

        header.classList.toggle('is-scrolled', window.scrollY > 24)
    }

    syncHeaderState()
    window.addEventListener('scroll', syncHeaderState, { passive: true })

    if ('IntersectionObserver' in window && revealItems.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible')
                    observer.unobserve(entry.target)
                }
            })
        }, { threshold: 0.18 })

        revealItems.forEach((item) => observer.observe(item))
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'))
    }

    newsletterForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault()

            const emailInput = form.querySelector('input[name="email"]')
            const emailValue = emailInput?.value?.trim()

            if (!emailValue) {
                emailInput?.focus()
                return
            }

            const contactEmail = form.dataset.contactEmail?.trim()
            const whatsappUrl = form.dataset.whatsappUrl?.trim()
            const encodedMessage = encodeURIComponent(`Hola, quiero suscribirme para recibir novedades del hotel. Mi correo es: ${emailValue}`)

            if (contactEmail) {
                window.location.href = `mailto:${contactEmail}?subject=${encodeURIComponent('Suscripcion a novedades del hotel')}&body=${encodedMessage}`
                return
            }

            if (whatsappUrl) {
                const separator = whatsappUrl.includes('?') ? '&' : '?'
                window.open(`${whatsappUrl}${separator}text=${encodedMessage}`, '_blank', 'noopener')
            }
        })
    })

    if (heroLoader) {
        let loaderHidden = false
        const minimumVisibleAt = Date.now() + 3000
        const hideHeroLoader = () => {
            if (loaderHidden) {
                return
            }

            const remaining = minimumVisibleAt - Date.now()

            if (remaining > 0) {
                window.setTimeout(hideHeroLoader, remaining)
                return
            }

            loaderHidden = true
            heroLoader.classList.add('is-hidden')
        }

        const fallbackTimeout = window.setTimeout(hideHeroLoader, 3000)

        if (shouldSkipHeavyHeroMedia) {
            hideHeroLoader()
        } else if (heroVideo) {
            if (heroVideo.readyState >= 2) {
                hideHeroLoader()
            } else {
                heroVideo.addEventListener('loadeddata', hideHeroLoader, { once: true })
                heroVideo.addEventListener('canplay', hideHeroLoader, { once: true })
                heroVideo.addEventListener('error', hideHeroLoader, { once: true })
            }
        } else if (heroIframe) {
            heroIframe.addEventListener('load', hideHeroLoader, { once: true })
            heroIframe.addEventListener('error', hideHeroLoader, { once: true })
        } else {
            hideHeroLoader()
        }

        heroLoader.addEventListener('transitionend', () => {
            window.clearTimeout(fallbackTimeout)
        }, { once: true })
    }

    if (heroVideo && !shouldSkipHeavyHeroMedia) {
        heroVideo.muted = true
        heroVideo.loop = true
        heroVideo.playsInline = true
        heroVideo.setAttribute('muted', '')
        heroVideo.setAttribute('loop', '')
        heroVideo.setAttribute('playsinline', '')

        const keepHeroVideoPlaying = () => {
            const playPromise = heroVideo.play()

            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {})
            }
        }

        if (heroVideo.readyState >= 2) {
            keepHeroVideoPlaying()
        } else {
            heroVideo.addEventListener('loadeddata', keepHeroVideoPlaying, { once: true })
        }

        heroVideo.addEventListener('ended', () => {
            heroVideo.currentTime = 0
            keepHeroVideoPlaying()
        })

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && heroVideo.paused) {
                keepHeroVideoPlaying()
            }
        })
    }

    if (announcementsModalElement) {
        window.setTimeout(() => {
            const modal = new bootstrap.Modal(announcementsModalElement)
            modal.show()
        }, heroLoader ? 3200 : 600)
    }

    const countryTemplate = (option) => {
        if (!option.id) {
            return option.text
        }

        const countryCode = option.element?.dataset?.countryCode
        const countryFlag = option.element?.dataset?.countryFlag
        const $template = $('<span class="public-country-option"></span>')
        const $flag = $('<span class="public-country-flag"></span>').appendTo($template)

        if (countryFlag) {
            $('<img>')
                .attr('src', countryFlag)
                .attr('alt', countryCode ? `Bandera ${countryCode}` : 'Bandera')
                .attr('loading', 'lazy')
                .appendTo($flag)
        }

        $('<span class="public-country-name"></span>').text(option.text).appendTo($template)

        return $template
    }

    const normalizeCountryName = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()
        .toLowerCase()

    const countryDisplayNames = typeof Intl?.DisplayNames === 'function'
        ? new Intl.DisplayNames(['es'], { type: 'region' })
        : null

    const hydrateCountryOptionsFromApi = async (select) => {
        const apiUrl = select.dataset.countryApi

        if (!apiUrl || select.dataset.countriesLoaded === 'true') {
            return
        }

        const selectedCountry = select.dataset.selectedCountry || select.value || 'Bolivia'
        const controller = new AbortController()
        const timeoutId = window.setTimeout(() => controller.abort(), 7000)

        try {
            const response = await fetch(apiUrl, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })

            if (!response.ok) {
                throw new Error('No se pudo cargar la lista de paises.')
            }

            const countries = await response.json()
            const fragment = document.createDocumentFragment()
            let selectedExists = false

            countries
                .map((country) => {
                    const code = country.cca2 || country.code || country.iso_3166_1_alpha2

                    return {
                        name: country.translations?.spa?.common || (code && countryDisplayNames?.of(code)) || country.name?.common || country.name,
                        code,
                        flag: country.flags?.svg || country.flags?.png || country.flag_url || (code ? `https://flagcdn.com/w40/${code.toLowerCase()}.png` : ''),
                    }
                })
                .filter((country) => country.name && country.code)
                .sort((first, second) => first.name.localeCompare(second.name, 'es'))
                .forEach((country) => {
                    const option = new Option(country.name, country.name, false, normalizeCountryName(country.name) === normalizeCountryName(selectedCountry))
                    option.dataset.countryCode = country.code
                    option.dataset.countryFlag = country.flag
                    fragment.appendChild(option)

                    if (normalizeCountryName(country.name) === normalizeCountryName(selectedCountry)) {
                        selectedExists = true
                    }
                })

            if (!selectedExists && selectedCountry) {
                const selectedOption = new Option(selectedCountry, selectedCountry, false, true)
                fragment.prepend(selectedOption)
            }

            select.replaceChildren(fragment)
            select.dataset.countriesLoaded = 'true'
        } catch (error) {
            console.warn('No se pudo cargar paises desde la API publica. Se usara la lista local.', error)
        } finally {
            window.clearTimeout(timeoutId)
        }
    }

    if (countrySelects.length && typeof $.fn.select2 === 'function') {
        countrySelects.forEach(async (select) => {
            await hydrateCountryOptionsFromApi(select)

            const $select = $(select)

            if ($select.data('select2')) {
                $select.trigger('change.select2')
                return
            }

            $select.select2({
                width: '100%',
                placeholder: select.dataset.placeholder || 'Selecciona tu pais',
                templateResult: countryTemplate,
                templateSelection: countryTemplate,
                dropdownParent: $select.closest('.booking-step-panel'),
            })
        })
    }

    roomGalleries.forEach((gallery) => {
        const slides = Array.from(gallery.querySelectorAll('img'))

        if (slides.length <= 1) {
            return
        }

        let activeIndex = Math.max(slides.findIndex((slide) => slide.classList.contains('is-active')), 0)
        slides.forEach((slide, index) => slide.classList.toggle('is-active', index === activeIndex))

        window.setInterval(() => {
            slides[activeIndex].classList.remove('is-active')
            activeIndex = (activeIndex + 1) % slides.length
            slides[activeIndex].classList.add('is-active')
        }, 2500)
    })
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPublicSite)
} else {
    initPublicSite()
}
