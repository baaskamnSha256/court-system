import Alpine from 'alpinejs'
import { parseMongolianRegistryDemographics } from './mongolian-registry'

window.Alpine = Alpine
window.parseMongolianRegistryDemographics = parseMongolianRegistryDemographics
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
  requireNotesSummary: cfg.requireNotesSummary !== false,
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
  summaryError: '',
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
  hasNotesSummary() {
    return String(this.notesHandoverText || '').trim() !== ''
  },
  submitNotesHandover() {
    this.summaryError = ''
    if (this.requireNotesSummary && !this.hasNotesSummary()) {
      this.summaryError = 'Шүүх хуралдааны тойм оруулна уу.'
      this.$nextTick(() => {
        document.getElementById(`notes-handover-textarea-${this.hearingId}`)?.focus()
      })
      return
    }
    document.getElementById(this.formId)?.requestSubmit()
  },
  openEditModal() {
    this.notesHandoverText = this.savedNotesHandoverText
    this.initialNotesHandoverText = this.savedNotesHandoverText
    this.decisionStatus = this.savedDecisionStatus
    this.summaryError = ''
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
    this.summaryError = ''
    this.$nextTick(() => {
      this.syncFormControlsFromSaved()
      this.broadcastModalReset()
    })
  },
}))

function cloneNotesPaneInitial(initial) {
  if (typeof structuredClone === 'function') {
    try {
      return structuredClone(initial)
    } catch {
      // fall through
    }
  }
  return JSON.parse(JSON.stringify(initial))
}

window.notesDefendantPaneState = window.notesDefendantPaneState || function notesDefendantPaneState(initial) {
  const state = cloneNotesPaneInitial(initial)
  return {
    ...state,
    selectDecidedMatter(id) {
      const intId = Number(id)
      if (!intId) return
      const decidedIds = Array.isArray(this.decidedMatterIds)
        ? this.decidedMatterIds.map((item) => Number(item)).filter((item) => item > 0)
        : []
      if (decidedIds.includes(intId)) {
        this.decidedMatterIds = decidedIds.filter((item) => item !== intId)
        this.matterDecisions = this.matterDecisions.filter((row) => Number(row.matter_category_id) !== intId)
      } else {
        this.decidedMatterIds = [...decidedIds, intId]
        if (!this.matterDecisions.some((row) => Number(row.matter_category_id) === intId)) {
          this.matterDecisions.push({ matter_category_id: intId, decision_type: 'sentence' })
        }
      }
      this.syncPrimaryDecidedMatter()
      if (!this.activeMatterId || !this.decidedMatterIds.includes(Number(this.activeMatterId))) {
        this.activeMatterId = this.decidedMatterId
      }
      this.matterOpen = false
    },
    clearDecidedMatter() {
      this.decidedMatterIds = []
      this.matterDecisions = []
      this.decidedMatterId = null
      this.activeMatterId = null
      this.decisionTab = 'sentence'
      this.outcomeTrack = 'sentence'
      this.specialOutcome = ''
      this.terminationKind = ''
      this.terminationNote = ''
      this.allocRows = []
      this.allocKey = (this.allocKey || 0) + 1
      this.matterQuery = ''
      this.matterOpen = false
      this.matterActiveIndex = -1
    },
    syncPrimaryDecidedMatter() {
      const first = Array.isArray(this.decidedMatterIds)
        ? this.decidedMatterIds.map((id) => Number(id)).find((id) => id > 0)
        : null
      this.decidedMatterId = first || null
    },
    setActiveMatter(matterId) {
      const intId = Number(matterId)
      if (!intId) return
      this.activeMatterId = intId
      this.decidedMatterId = intId
      const type = this.getMatterDecisionType(intId)
      this.decisionTab = type === 'no_sentence' ? 'probation' : type
      this.onDecisionTabChange()
    },
    getMatterDecisionType(matterId) {
      const found = this.matterDecisions.find((row) => Number(row.matter_category_id) === Number(matterId))
      return found ? found.decision_type : 'sentence'
    },
    setMatterDecisionType(matterId, decisionType) {
      const idx = this.matterDecisions.findIndex((row) => Number(row.matter_category_id) === Number(matterId))
      if (idx < 0) {
        this.matterDecisions.push({ matter_category_id: Number(matterId), decision_type: decisionType })
        return
      }
      this.matterDecisions[idx].decision_type = decisionType
    },
    decidedMatterNameById(id) {
      const found = this.matterOptions.find((opt) => Number(opt.id) === Number(id))
      return found ? found.name : ''
    },
    filteredMatterOptions() {
      const q = (this.matterQuery || '').trim().toLowerCase()
      if (q === '') return this.matterOptions
      return this.matterOptions.filter((opt) => String(opt.name || '').toLowerCase().includes(q))
    },
    openMatterDropdown() {
      this.matterOpen = true
      const items = this.filteredMatterOptions()
      this.matterActiveIndex = items.length > 0 ? 0 : -1
    },
    moveMatterHighlight(step) {
      const items = this.filteredMatterOptions()
      if (items.length < 1) {
        this.matterActiveIndex = -1
        return
      }
      if (this.matterActiveIndex < 0) {
        this.matterActiveIndex = 0
        return
      }
      this.matterActiveIndex = (this.matterActiveIndex + step + items.length) % items.length
    },
    chooseMatterByKeyboard() {
      const items = this.filteredMatterOptions()
      if (!this.matterOpen || items.length < 1) return
      const idx = this.matterActiveIndex >= 0 ? this.matterActiveIndex : 0
      const opt = items[idx] || items[0]
      if (opt) this.selectDecidedMatter(opt.id)
    },
    onDecisionTabChange() {
      if (!this.decidedMatterId) return
      this.setMatterDecisionType(this.decidedMatterId, this.decisionTab === 'probation' ? 'no_sentence' : this.decisionTab)
      if (this.decisionTab === 'sentence') {
        this.outcomeTrack = 'sentence'
        this.specialOutcome = ''
        this.terminationKind = ''
        this.terminationNote = ''
      } else if (this.decisionTab === 'probation') {
        this.outcomeTrack = 'no_sentence'
        if (
          ![
            'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
            'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
            'Хорих ял оногдуулахгүйгээр тэнссэн',
            'Эрүүгийн хариуцлагаас чөлөөлсөн',
          ].includes(this.specialOutcome)
        ) {
          this.specialOutcome = ''
        }
        this.terminationKind = ''
        this.terminationNote = ''
        this.clearAllocationPunishments()
        this.allocRows = []
        this.allocKey = (this.allocKey || 0) + 1
      } else if (this.decisionTab === 'dismiss' || this.decisionTab === 'acquit') {
        this.outcomeTrack = 'termination'
        this.terminationKind = this.decisionTab === 'dismiss' ? 'dismiss' : 'acquit'
        this.specialOutcome = ''
        this.clearAllocationPunishments()
        this.allocRows = []
        this.allocKey = (this.allocKey || 0) + 1
      }
    },
    addAllocRow() {
      if (this.decisionTab !== 'sentence') return
      this.allocRows.push({
        matter_category_id: '',
        punishments: {
          fine: { fine_units: '', damage_amount: '' },
          community_service: { hours: '' },
          travel_restriction: { years: '', months: '' },
          imprisonment_open: { years: '', months: '' },
          imprisonment_closed: { years: '', months: '' },
          rights_ban_public_service: { years: '', months: '' },
          rights_ban_professional_activity: { years: '', months: '' },
          rights_ban_driving: { years: '', months: '' },
        },
      })
    },
    removeAllocRow(index) {
      this.allocRows.splice(index, 1)
    },
    clearAllocationPunishments() {
      if (!Array.isArray(this.allocRows)) {
        this.allocRows = []
        return
      }
      this.allocRows = this.allocRows.map((row) => ({
        ...row,
        punishments: {
          fine: { fine_units: '', damage_amount: '' },
          community_service: { hours: '' },
          travel_restriction: { years: '', months: '' },
          imprisonment_open: { years: '', months: '' },
          imprisonment_closed: { years: '', months: '' },
          rights_ban_public_service: { years: '', months: '' },
          rights_ban_professional_activity: { years: '', months: '' },
          rights_ban_driving: { years: '', months: '' },
        },
      }))
    },
  }
}

Alpine.start()