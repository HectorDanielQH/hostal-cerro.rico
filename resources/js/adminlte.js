/**
 * AdminLTE 4 + Bootstrap 5 entry point.
 *
 * Published by `php artisan adminlte:install`. Add this file to your
 * vite.config.js input array, then `npm run dev` / `npm run build`.
 */

// Bootstrap (provides dropdowns, modals, tooltips, offcanvas, etc.)
import * as bootstrap from 'bootstrap'
import $ from 'jquery'

window.bootstrap = bootstrap
window.$ = $
window.jQuery = $

// OverlayScrollbars — AdminLTE uses it for the sidebar scroller (optional)
import { OverlayScrollbars } from 'overlayscrollbars'

// AdminLTE plugins (PushMenu, Treeview, CardWidget, FullScreen, DirectChat,
// Layout, accessibility). The data-lte-* API is wired on DOMContentLoaded.
import 'admin-lte'

/**
 * Initialise an optional plugin only when its global is present.
 * Plugin libraries (ApexCharts, jsVectorMap, FullCalendar, Sortable) are
 * loaded lazily via the @pluginScripts directive as global <script> tags,
 * so we feature-detect before touching them.
 */
function whenReady(fn) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fn)
  } else {
    fn()
  }
}

function parseConfig(el, attr) {
  const raw = el.getAttribute(attr)
  if (!raw) return {}
  try {
    return JSON.parse(raw)
  } catch (e) {
    console.warn('AdminLTE: invalid JSON in', attr, e)
    return {}
  }
}

// --- ApexCharts ------------------------------------------------------------
function initCharts() {
  if (typeof window.ApexCharts === 'undefined') return
  document.querySelectorAll('[data-apexchart]').forEach((el) => {
    if (el.dataset.apexchartReady) return
    const config = parseConfig(el, 'data-apexchart-config')
    try {
      new window.ApexCharts(el, config).render()
      el.dataset.apexchartReady = 'true'
    } catch (e) {
      console.warn('AdminLTE: ApexCharts init failed (check the chart config)', e)
    }
  })
}

// --- jsVectorMap -----------------------------------------------------------
function initVectorMaps() {
  if (typeof window.jsVectorMap === 'undefined') return
  document.querySelectorAll('[data-jsvectormap]').forEach((el) => {
    if (el.dataset.jsvectormapReady || !el.id) return
    const config = parseConfig(el, 'data-jsvectormap-config')
    try {
      new window.jsVectorMap({ selector: '#' + el.id, ...config })
      el.dataset.jsvectormapReady = 'true'
    } catch (e) {
      console.warn('AdminLTE: jsVectorMap init failed (is the map data file loaded?)', e)
    }
  })
}

// --- FullCalendar ----------------------------------------------------------
function initCalendars() {
  if (typeof window.FullCalendar === 'undefined') return
  document.querySelectorAll('[data-fullcalendar]').forEach((el) => {
    if (el.dataset.fullcalendarReady) return
    const config = parseConfig(el, 'data-fullcalendar-config')
    new window.FullCalendar.Calendar(el, config).render()
    el.dataset.fullcalendarReady = 'true'
  })
}

// --- SortableJS (generic lists + kanban boards) ----------------------------
function initSortables() {
  if (typeof window.Sortable === 'undefined') return

  // Generic sortable lists — items in the same group can be dragged between lists.
  document.querySelectorAll('[data-sortable]').forEach((el) => {
    if (el.dataset.sortableReady) return
    const options = parseConfig(el, 'data-sortable-options')
    window.Sortable.create(el, { animation: 150, ...options })
    el.dataset.sortableReady = 'true'
  })

  // Kanban boards — every lane shares one group so cards move between lanes.
  document.querySelectorAll('[data-sortable-kanban]').forEach((board) => {
    board.querySelectorAll('[data-sortable-group]').forEach((lane) => {
      if (lane.dataset.sortableReady) return
      window.Sortable.create(lane, {
        group: 'kanban-' + (board.id || 'board'),
        animation: 150,
      })
      lane.dataset.sortableReady = 'true'
    })
  })
}

function initDropdowns() {
  if (!window.bootstrap?.Dropdown) return

  document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
    window.bootstrap.Dropdown.getOrCreateInstance(el)
  })
}

function initUserMenuFallback() {
  const toggle = document.getElementById('adminlte-usermenu-toggle')
  const menu = toggle?.nextElementSibling

  if (!toggle || !menu) return

  toggle.addEventListener('click', (event) => {
    event.preventDefault()

    if (window.bootstrap?.Dropdown) {
      window.bootstrap.Dropdown.getOrCreateInstance(toggle).toggle()
      return
    }

    const isOpen = menu.classList.contains('show')
    menu.classList.toggle('show', !isOpen)
    toggle.setAttribute('aria-expanded', String(!isOpen))
  })

  document.addEventListener('click', (event) => {
    if (toggle.contains(event.target) || menu.contains(event.target)) return

    menu.classList.remove('show')
    toggle.setAttribute('aria-expanded', 'false')
  })
}

function initColorModeToggle() {
  const storageKey = 'adminlte-color-mode'
  const themeButtons = document.querySelectorAll('[data-bs-theme-value]')
  const themeIcons = document.querySelectorAll('[data-lte-theme-icon]')
  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

  if (!themeButtons.length) return

  const getStoredTheme = () => {
    try {
      return localStorage.getItem(storageKey)
    } catch {
      return null
    }
  }

  const storeTheme = (theme) => {
    try {
      localStorage.setItem(storageKey, theme)
    } catch {
      // The theme still changes for the current page if storage is blocked.
    }
  }

  const getPreferredTheme = () => getStoredTheme() || 'auto'
  const getResolvedTheme = (theme) => (theme === 'auto' ? (mediaQuery.matches ? 'dark' : 'light') : theme)

  const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-bs-theme', getResolvedTheme(theme))
    document.documentElement.setAttribute('data-theme-preference', theme)
  }

  const showActiveTheme = (theme) => {
    themeButtons.forEach((button) => {
      const isActive = button.getAttribute('data-bs-theme-value') === theme
      button.classList.toggle('active', isActive)
      button.setAttribute('aria-pressed', String(isActive))
      button.querySelector('.bi-check-lg')?.classList.toggle('d-none', !isActive)
    })

    themeIcons.forEach((icon) => {
      icon.classList.toggle('d-none', icon.getAttribute('data-lte-theme-icon') !== theme)
    })
  }

  const setTheme = (theme) => {
    storeTheme(theme)
    applyTheme(theme)
    showActiveTheme(theme)
  }

  const initialTheme = getPreferredTheme()
  applyTheme(initialTheme)
  showActiveTheme(initialTheme)

  themeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      setTheme(button.getAttribute('data-bs-theme-value') || 'auto')
    })
  })

  mediaQuery.addEventListener?.('change', () => {
    if (getPreferredTheme() === 'auto') {
      applyTheme('auto')
    }
  })
}

// --- Sidebar treeview a11y --------------------------------------------------
// AdminLTE's Treeview toggles .menu-open on the <li>; mirror that state onto
// the toggle link's aria-expanded so screen readers track open/closed submenus.
function initTreeviewA11y() {
  const sidebar = document.querySelector('.app-sidebar')
  if (!sidebar || typeof MutationObserver === 'undefined') return
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((m) => {
      const link = m.target.querySelector(':scope > a.nav-link[aria-expanded]')
      if (link) link.setAttribute('aria-expanded', m.target.classList.contains('menu-open') ? 'true' : 'false')
    })
  })
  sidebar.querySelectorAll('li.nav-item').forEach((li) => {
    if (li.querySelector(':scope > ul.nav-treeview')) {
      observer.observe(li, { attributes: true, attributeFilter: ['class'] })
    }
  })
}

whenReady(() => {
  // Wire OverlayScrollbars to the sidebar (matches the AdminLTE demo behaviour)
  const sidebar = document.querySelector('.sidebar-wrapper')
  if (sidebar && window.innerWidth > 992) {
    OverlayScrollbars(sidebar, {
      scrollbars: { theme: 'os-theme-light', autoHide: 'leave', clickScroll: true },
    })
  }

  initCharts()
  initVectorMaps()
  initCalendars()
  initSortables()
  initDropdowns()
  initUserMenuFallback()
  initColorModeToggle()
  initTreeviewA11y()
})
