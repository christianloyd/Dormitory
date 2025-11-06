<!-- Offline Calendar (replace your Google iframe with this) -->
<div class="calendar-box offline-calendar">
  <div class="oc-header">
    <div class="oc-nav">
      <button id="oc-prev" class="oc-btn">&laquo; Prev</button>
      <button id="oc-today" class="oc-btn">Today</button>
      <button id="oc-next" class="oc-btn">Next &raquo;</button>
    </div>
    <div class="oc-title" id="oc-title">MONTH YEAR</div>
  </div>

  <div class="oc-grid-wrapper">
    <table class="oc-grid" id="oc-grid" role="grid" aria-label="Calendar">
      <thead>
        <tr>
          <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
        </tr>
      </thead>
      <tbody id="oc-body">
        <!-- JS will generate month rows here -->
      </tbody>
    </table>
  </div>
</div>

<!-- Modal for notes -->
<div id="oc-modal" class="oc-modal" aria-hidden="true">
  <div class="oc-modal-content">
    <h4 id="oc-modal-date">Date</h4>
    <textarea id="oc-note" rows="6" placeholder="Write note for this date..."></textarea>
    <div class="oc-modal-actions">
      <button id="oc-save" class="oc-btn oc-btn-primary">Save</button>
      <button id="oc-delete" class="oc-btn oc-btn-danger">Delete</button>
      <button id="oc-cancel" class="oc-btn">Cancel</button>
    </div>
  </div>
</div>

<style>
/* Calendar styles — keeps similar look/size to your dashboard chart-box */
.offline-calendar {
  background: #fff;
  border-radius: 8px;
  padding: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  width: 100%;
  height: 435px; /* same visual height as your previous calendar area */
  box-sizing: border-box;
  display:flex;
  flex-direction:column;
  gap:8px;


}


/* header */
.oc-header { display:flex; justify-content:space-between; align-items:center; gap:10px; }
.oc-nav { display:flex; gap:8px; align-items:center; }
.oc-title { font-weight:700; font-size:1.05rem; color:#333; }

/* buttons */
.oc-btn {
  background:#f0f0f0;
  border:1px solid #ddd;
  padding:6px 10px;
  border-radius:6px;
  cursor:pointer;
  font-size:0.9rem;
}
.oc-btn:hover { filter:brightness(.98); }
.oc-btn-primary { background:#5A7D7C; color:#fff; border-color:#4e6b69; }
.oc-btn-danger { background:#dc3545; color:#fff; border-color:#b02a37; }

/* grid wrapper to allow inner scrolling if needed */
.oc-grid-wrapper {
  flex:1;
  overflow:auto;
  padding-top:4px;
}

/* table grid */
.oc-grid {
  width:100%;
  border-collapse:collapse;
  table-layout:fixed;
  min-width: 420px; /* allows smaller screens to scroll */
}
.oc-grid thead th {
  text-align:center;
  padding:8px 6px;
  background:#f6f8f8;
  color:#333;
  font-weight:600;
  font-size:0.9rem;
  border-bottom:1px solid #e6eceb;
}
.oc-grid tbody td {
  vertical-align:top;
  border:1px solid #eee;
  height:95px; /* fixed cell height for consistent look */
  padding:6px;
  box-sizing:border-box;
  position:relative;
  cursor:pointer;
}
.oc-day-number {
  font-weight:600;
  font-size:0.95rem;
  color:#222;
}
.oc-note-indicator {
  position:absolute;
  right:6px;
  top:6px;
  width:10px;
  height:10px;
  border-radius:50%;
  background:#f39c12;
  box-shadow:0 0 0 2px rgba(243,156,18,0.12);
}

/* muted style for cells out of month */
.oc-muted { background:#fbfcfb; color:#999; }

/* modal */
.oc-modal {
  position:fixed;
  inset:0;
  display:none;
  align-items:center;
  justify-content:center;
  background:rgba(0,0,0,0.4);
  z-index:2000;
}
.oc-modal[aria-hidden="false"] { display:flex; }
.oc-modal-content {
  background:#fff;
  width:520px;
  max-width:92%;
  border-radius:8px;
  padding:16px;
  box-shadow:0 10px 30px rgba(0,0,0,0.25);
}
#oc-note {
  width:100%;
  box-sizing:border-box;
  padding:10px;
  margin-top:8px;
  border-radius:6px;
  border:1px solid #ddd;
  resize:vertical;
  font-family:inherit;
}

/* modal actions */
.oc-modal-actions {
  display:flex;
  justify-content:flex-end;
  gap:8px;
  margin-top:10px;
}

/* small screens */
@media(max-width:700px){
  .oc-grid tbody td { height:80px; padding:4px; }
}

</style>

<script>
// Offline Calendar JS
(function() {
  const titleEl = document.getElementById('oc-title');
  const bodyEl = document.getElementById('oc-body');
  const prevBtn = document.getElementById('oc-prev');
  const nextBtn = document.getElementById('oc-next');
  const todayBtn = document.getElementById('oc-today');

  const modal = document.getElementById('oc-modal');
  const modalDateEl = document.getElementById('oc-modal-date');
  const noteTextarea = document.getElementById('oc-note');
  const saveBtn = document.getElementById('oc-save');
  const deleteBtn = document.getElementById('oc-delete');
  const cancelBtn = document.getElementById('oc-cancel');

  // State
  let viewDate = new Date(); // current month view
  let activeCellDate = null;

  // Key for localStorage
  const STORAGE_KEY = 'offlineCalendarNotes_v1';

  function loadNotes() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (e) {
      console.error('Failed to parse saved notes', e);
      return {};
    }
  }
  function saveNotes(notes) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(notes));
  }

  function formatYMD(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
  }

  function render() {
    const notes = loadNotes();
    const year = viewDate.getFullYear();
    const month = viewDate.getMonth(); // 0-indexed
    titleEl.textContent = viewDate.toLocaleString(undefined, { month: 'long', year: 'numeric' });

    // compute first weekday (Monday = 0)
    const firstOfMonth = new Date(year, month, 1);
    // dayIndex: Monday=0 ... Sunday=6
    const dayIndex = (firstOfMonth.getDay() + 6) % 7;

    const daysInMonth = new Date(year, month+1, 0).getDate();

    // previous month days (for muted cells)
    const prevMonthDays = new Date(year, month, 0).getDate();

    // build rows (6 weeks)
    bodyEl.innerHTML = '';
    let dayCounter = 1;
    for (let week=0; week<6; week++) {
      const tr = document.createElement('tr');
      for (let dow=0; dow<7; dow++) {
        const td = document.createElement('td');

        // calculate cell index
        const cellIdx = week*7 + dow;

        if (cellIdx < dayIndex) {
          // previous month day
          const d = prevMonthDays - (dayIndex - 1 - cellIdx);
          td.classList.add('oc-muted');
          td.innerHTML = `<div class="oc-day-number">${d}</div>`;
          // not interactive
        } else if (dayCounter > daysInMonth) {
          // next month day
          const d = dayCounter - daysInMonth;
          td.classList.add('oc-muted');
          td.innerHTML = `<div class="oc-day-number">${d}</div>`;
          dayCounter++;
        } else {
          // current month day
          const realDate = new Date(year, month, dayCounter);
          const ymd = formatYMD(realDate);
          td.dataset.date = ymd;
          td.innerHTML = `<div class="oc-day-number">${dayCounter}</div>`;
          // if note exists, show indicator
          if (notes[ymd] && notes[ymd].trim() !== '') {
            const dot = document.createElement('span');
            dot.className = 'oc-note-indicator';
            td.appendChild(dot);
          }

          // click to edit note
          td.addEventListener('click', function(e){
            openModalForDate(ymd);
          });

          dayCounter++;
        }
        tr.appendChild(td);
      }
      bodyEl.appendChild(tr);
    }
  }

  function openModalForDate(ymd) {
    const notes = loadNotes();
    activeCellDate = ymd;
    modalDateEl.textContent = new Date(ymd).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    noteTextarea.value = notes[ymd] || '';
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    activeCellDate = null;
    modal.setAttribute('aria-hidden', 'true');
  }

  // save
  saveBtn.addEventListener('click', function() {
    if (!activeCellDate) return;
    const notes = loadNotes();
    const val = noteTextarea.value.trim();
    if (val === '') {
      delete notes[activeCellDate];
    } else {
      notes[activeCellDate] = val;
    }
    saveNotes(notes);
    closeModal();
    render();
  });

  // delete
  deleteBtn.addEventListener('click', function() {
    if (!activeCellDate) return;
    if (!confirm('Delete note for ' + activeCellDate + '?')) return;
    const notes = loadNotes();
    delete notes[activeCellDate];
    saveNotes(notes);
    closeModal();
    render();
  });

  cancelBtn.addEventListener('click', closeModal);

  // navigation
  prevBtn.addEventListener('click', function(){
    viewDate.setMonth(viewDate.getMonth() - 1);
    render();
  });
  nextBtn.addEventListener('click', function(){
    viewDate.setMonth(viewDate.getMonth() + 1);
    render();
  });
  todayBtn.addEventListener('click', function(){
    viewDate = new Date();
    render();
  });

  // close modal on outside click or Esc
  modal.addEventListener('click', function(e){
    if (e.target === modal) closeModal();
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeModal();
  });

  // initial render
  render();
})();
</script>
