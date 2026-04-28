import Alpine from 'alpinejs'

window.Alpine = Alpine
window.formatGroupedNumber = window.formatGroupedNumber || function formatGroupedNumber(rawValue) {
  const raw = String(rawValue || '')
  const digits = raw.replace(/[^0-9]+/g, '').replace(/^0+(?=[0-9])/, '')
  return digits.replace(/([0-9])(?=([0-9]{3})+$)/g, '$1,')
}
window.chipSelect = window.chipSelect || function chipSelect(config = {}) {
  return {
    options: Array.isArray(config.options) ? JSON.parse(JSON.stringify(config.options)) : [],
    selected: Array.isArray(config.selected) ? JSON.parse(JSON.stringify(config.selected)) : [],
    single: !!config.single,
    placeholder: config.placeholder || 'Сонгох...',
    nameId: config.nameId || 'ids[]',
    query: '',
    open: false,
    filteredOptions: [],
    openNow() {
      this.open = true
      this.refreshFiltered()
      this.$nextTick(() => {
        this.refreshFiltered()
        requestAnimationFrame(() => this.refreshFiltered())
      })
    },
    init() {
      if (this.single && this.selected.length > 1) this.selected = this.selected.slice(0, 1)
      this.refreshFiltered()
      this.$watch('query', () => this.refreshFiltered())
      this.$watch('open', (v) => {
        if (v) this.$nextTick(() => this.refreshFiltered())
      })
      const forceRender = () => {
        this.selected = [...this.selected]
        this.filteredOptions = [...this.filteredOptions]
        this.refreshFiltered()
      }
      this.$nextTick(forceRender)
      requestAnimationFrame(() => this.$nextTick(forceRender))
      setTimeout(forceRender, 50)
      setTimeout(forceRender, 200)
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') forceRender()
      })
    },
    refreshFiltered() {
      const q = (this.query || '').trim().toLowerCase()
      this.filteredOptions = this.options.filter((o) => {
        if (!q) return true
        const name = String(o?.name || '').toLowerCase()
        const id = String(o?.id ?? '').toLowerCase()
        return name.includes(q) || id.includes(q)
      })
    },
    isSelected(opt) {
      return this.selected.some((s) => s.id === opt.id)
    },
    toggle(opt) {
      if (this.single) {
        this.selected = [{ id: opt.id, name: opt.name }]
        this.open = false
        this.query = ''
        this.refreshFiltered()
        return
      }
      if (this.isSelected(opt)) this.selected = this.selected.filter((s) => s.id !== opt.id)
      else this.selected = [...this.selected, { id: opt.id, name: opt.name }]
    },
    remove(s) {
      this.selected = this.selected.filter((x) => x.id !== s.id)
    },
  }
}
// HTML5 validation messages in Mongolian
document.addEventListener(
  'invalid',
  (event) => {
    const el = event.target
    if (!(el instanceof HTMLInputElement || el instanceof HTMLSelectElement || el instanceof HTMLTextAreaElement)) {
      return
    }
    // Find label text if possible
    let labelText = ''
    if (el.id) {
      const label = document.querySelector(`label[for="${el.id}"]`)
      if (label) {
        labelText = label.textContent?.trim() || ''
      }
    }
    if (!labelText) {
      const parentLabel = el.closest('label')
      if (parentLabel) {
        labelText = parentLabel.textContent?.trim() || ''
      }
    }
    const base = labelText || 'Энэ талбар'
    let msg = ''
    if (el.validity.valueMissing) {
      msg = `${base}ыг бөглөнө үү.`
    } else if (el.validity.typeMismatch) {
      msg = `${base}ын утга буруу байна.`
    } else if (el.validity.patternMismatch) {
      msg = `${base}ын формат буруу байна.`
    } else if (el.validity.tooShort) {
      msg = `${base}ын урт дутуу байна.`
    } else if (el.validity.tooLong) {
      msg = `${base}ын урт хэтэрсэн байна.`
    } else if (el.validity.rangeUnderflow || el.validity.rangeOverflow) {
      msg = `${base}ын утгыг зөв хүрээнд оруулна уу.`
    } else if (el.validity.stepMismatch) {
      msg = `${base}ын утга зөв алхамтай таарахгүй байна.`
    } else {
      msg = `${base}ыг зөв бөглөнө үү.`
    }
    el.setCustomValidity(msg)
  },
  true,
)
document.addEventListener(
  'input',
  (event) => {
    const el = event.target
    if (el && typeof el.setCustomValidity === 'function') {
      el.setCustomValidity('')
    }
  },
  true,
)

Alpine.data('notesHandoverRow', (cfg) => ({
  hearingId: cfg.hearingId,
  formId: cfg.formId,
  openModal: false,
  modalGeneration: 0,
  savedNotesHandoverText: cfg.savedNotesHandoverText ?? '',
  savedDecisionStatus: cfg.savedDecisionStatus ?? '',
  savedClerkId: cfg.savedClerkId ?? '',
  savedNotesHandoverIssued: !!cfg.savedNotesHandoverIssued,
  savedDefendants: Array.isArray(cfg.savedDefendants) ? cfg.savedDefendants : [],
  notesHandoverText: cfg.savedNotesHandoverText ?? '',
  initialNotesHandoverText: cfg.savedNotesHandoverText ?? '',
  decisionStatus: cfg.savedDecisionStatus ?? '',
  formatGroupedValue(rawValue) {
    if (window.formatGroupedNumber) {
      return window.formatGroupedNumber(rawValue)
    }
    const raw = String(rawValue || '')
    const digits = raw.replace(/[^0-9]+/g, '').replace(/^0+(?=[0-9])/, '')
    return digits.replace(/([0-9])(?=([0-9]{3})+$)/g, '$1,')
  },
  formatGroupedInput(event) {
    const input = event?.target
    if (!input) {
      return
    }
    input.value = this.formatGroupedValue(input.value)
  },
  syncFormControlsFromSaved() {
    const form = document.getElementById(this.formId)
    if (!form) {
      return
    }
    const pickEnabled = (named) => {
      if (!named) {
        return null
      }
      const list = named instanceof RadioNodeList ? Array.from(named) : [named]
      return list.find((el) => el && !el.disabled) ?? null
    }
    const clerk = pickEnabled(form.elements.namedItem('clerk_id'))
    if (clerk instanceof HTMLSelectElement) {
      clerk.value = this.savedClerkId ? String(this.savedClerkId) : ''
    }
    const issued = pickEnabled(form.elements.namedItem('notes_handover_issued'))
    if (issued instanceof HTMLInputElement && issued.type === 'checkbox') {
      issued.checked = this.savedNotesHandoverIssued
    }
  },
  broadcastModalReset() {
    window.dispatchEvent(
      new CustomEvent('notes-handover-modal-open', {
        detail: {
          hearingId: this.hearingId,
          modalGeneration: this.modalGeneration,
          defendants: this.savedDefendants,
        },
      }),
    )
  },
  openEditModal() {
    this.notesHandoverText = this.savedNotesHandoverText
    this.initialNotesHandoverText = this.savedNotesHandoverText
    this.decisionStatus = this.savedDecisionStatus
    this.modalGeneration += 1
    this.openModal = true
    this.$nextTick(() => {
      this.syncFormControlsFromSaved()
      this.broadcastModalReset()
    })
  },
  cancel() {
    this.openModal = false
    this.notesHandoverText = this.savedNotesHandoverText
    this.initialNotesHandoverText = this.savedNotesHandoverText
    this.decisionStatus = this.savedDecisionStatus
    this.$nextTick(() => {
      this.syncFormControlsFromSaved()
      this.broadcastModalReset()
    })
  },
}))

Alpine.start()