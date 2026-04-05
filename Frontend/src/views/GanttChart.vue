<template>
  <div>
    <PageHeader title="График Ганта" description="Визуализация расписания обучения">
      <div class="flex items-center gap-2">
        <!-- Навигация по временной шкале -->
        <Button variant="outline" size="icon" @click="navigate(-1)">
          <ChevronLeft class="w-4 h-4" />
        </Button>

        <select
          v-model="scale"
          class="h-10 px-3 rounded-md border border-input bg-background text-sm"
        >
          <option v-for="(cfg, key) in SCALES" :key="key" :value="key">
            {{ cfg.label }}
          </option>
        </select>

        <Button variant="outline" size="icon" @click="navigate(1)">
          <ChevronRight class="w-4 h-4" />
        </Button>

        <Button variant="outline" @click="goToday">Сегодня</Button>

        <!-- Экспорт -->
        <Button variant="outline" @click="exportGantt" :disabled="exporting">
          <Download class="w-4 h-4 mr-1.5" />
          {{ exporting ? "Экспорт…" : "Экспорт" }}
        </Button>
      </div>
    </PageHeader>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="w-8 h-8 border-4 border-muted border-t-primary rounded-full animate-spin" />
    </div>

    <!-- Gantt Chart -->
    <div v-show="!loading" class="gantt-outer bg-card rounded-2xl border border-border overflow-hidden">
      <div ref="ganttContainer" class="gantt-dhtmlx-target" style="width: 100%; height: 520px;" />
    </div>

    <!-- Конфликты -->
    <div
      v-if="conflictedGroups.length"
      class="mt-6 rounded-2xl border border-destructive/20 bg-destructive/5 p-4"
    >
      <h3 class="text-sm font-semibold mb-3 text-destructive flex items-center gap-2">
        ⚠️ Конфликты расписания
        <span class="text-xs font-normal text-muted-foreground">
          — группы по одному курсу с одинаковыми датами
        </span>
      </h3>

      <div
        v-for="group in conflictedGroups"
        :key="group.id"
        class="flex items-start gap-3 text-sm mb-2 last:mb-0 p-3 rounded-xl bg-background border border-border"
      >
        <div
          class="w-2 h-2 rounded-full mt-1.5 shrink-0"
          :style="{ background: group.color || '#10B981' }"
        />
        <div class="flex-1 min-w-0">
          <div class="font-medium truncate">{{ group.text }}</div>
          <div class="text-muted-foreground text-xs mt-0.5">
            {{ group.start_date }} — {{ group.end_date }}
          </div>
          <div class="text-destructive text-xs mt-1">
            Пересечение с группой(ами) ID: <b>{{ group.conflict_ids.join(", ") }}</b>
          </div>
        </div>
        <button
          class="text-xs text-primary font-medium whitespace-nowrap hover:underline"
          @click="router.push({ name: 'training-groups.show', params: { id: group.id } })"
        >
          Открыть →
        </button>
      </div>
    </div>

    <!-- Color Picker (Teleport) -->
    <Teleport to="body">
      <div
        v-if="colorPicker.visible"
        class="fixed inset-0 z-50"
        @click.self="closeColorPicker"
      />
      <Transition name="popup-fade">
        <div
          v-if="colorPicker.visible"
          class="fixed z-50 bg-card border border-border rounded-2xl shadow-xl p-4"
          :style="{ top: colorPicker.y + 'px', left: colorPicker.x + 'px', minWidth: '220px' }"
        >
          <div class="text-xs font-semibold text-muted-foreground mb-3">
            Цвет группы
          </div>

          <div class="grid grid-cols-5 gap-2 mb-3">
            <button
              v-for="color in palette"
              :key="color"
              class="w-8 h-8 rounded-full border-2 transition-all hover:scale-110"
              :class="colorPicker.currentColor === color
                ? 'border-foreground scale-110'
                : 'border-transparent'"
              :style="{ background: color }"
              @click="applyColor(color)"
            />
          </div>

          <div class="flex items-center gap-2 pt-2 border-t border-border">
            <label class="text-xs text-muted-foreground whitespace-nowrap">Другой:</label>
            <input
              type="color"
              :value="colorPicker.currentColor"
              class="w-8 h-8 rounded-full border-none cursor-pointer bg-transparent p-0 color-input"
              @change="applyColor($event.target.value)"
            />
            <span class="text-xs text-muted-foreground font-mono flex-1">
              {{ colorPicker.currentColor }}
            </span>
            <button
              class="text-xs text-muted-foreground hover:text-foreground"
              @click="closeColorPicker"
            >✕</button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * ============================================================================
 * GanttChart.vue — DHTMLX Gantt для системы корпоративного обучения
 * ============================================================================
 *
 * Оптимизация: данные загружаются только для видимого диапазона дат + буфер.
 * При навигации стрелками подгружаются новые данные, старые кешируются.
 *
 * Зависимости:
 *   npm install dhtmlx-gantt
 *
 * Что делает:
 *   - Рендерит диаграмму Ганта через dhtmlx-gantt (GPL)
 *   - Загружает данные ТОЛЬКО для видимого окна (from/to с буфером)
 *   - Кеширует загруженные группы, чтобы не перезапрашивать при возврате
 *   - Drag-and-drop перетаскивание баров → PATCH /api/gantt/{id}/dates
 *   - Переключение масштабов: неделя / месяц / квартал
 *   - Прогресс-бар внутри каждой полосы
 *   - Кастомные тултипы с деталями группы
 *   - Подсветка конфликтов (пульсирующая рамка)
 *   - Маркер «сегодня»
 *   - Выбор цвета группы → PATCH /api/gantt/{id}/color
 *   - Экспорт через /api/gantt/export
 *   - Клик по бару → переход к карточке группы
 */

import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import { useRouter } from "vue-router";
import apiClient from "@/api/axios";
import {
  ChevronLeft,
  ChevronRight,
  Download,
} from "lucide-vue-next";
import { addDays, format, parseISO, differenceInDays } from "date-fns";

// ── DHTMLX Gantt ──────────────────────────────────────────────────────────────
import { gantt } from "dhtmlx-gantt";
import "dhtmlx-gantt/codebase/dhtmlxgantt.css";

import PageHeader from "@/components/ui/PageHeader.vue";
import Button from "@/components/ui/button.vue";

const router = useRouter();

// ─── Scales Config ────────────────────────────────────────────────────────────
/**
 * Каждый масштаб определяет:
 *   - navDays: на сколько дней сдвигается viewport при нажатии стрелки
 *   - rangeDays: ширина видимого окна (без буфера)
 *   - bufferDays: дополнительный запас по краям для плавной навигации
 *   - zoomLevel: индекс уровня зума DHTMLX
 */
const SCALES = {
  week: {
    label: "Неделя",
    navDays: 7,
    rangeDays: 30,
    bufferDays: 14,
    zoomLevel: 0,
  },
  month: {
    label: "Месяц",
    navDays: 30,
    rangeDays: 90,
    bufferDays: 30,
    zoomLevel: 1,
  },
  quarter: {
    label: "Квартал",
    navDays: 90,
    rangeDays: 270,
    bufferDays: 60,
    zoomLevel: 2,
  },
};

// ─── State ────────────────────────────────────────────────────────────────────
const groups = ref([]);
const palette = ref([]);
const loading = ref(true);
const exporting = ref(false);
const scale = ref("month");
const viewStart = ref(new Date());
const ganttContainer = ref(null);
const ganttReady = ref(false);

/**
 * Кеш загруженных групп.
 * Ключ — group.id, значение — объект группы.
 * При каждом запросе к API мы мержим новые данные в кеш,
 * а groups.value собирается из кеша по видимому диапазону.
 */
const groupsCache = new Map();

/**
 * Запоминаем, какие диапазоны дат мы уже запрашивали,
 * чтобы не делать повторных запросов при возврате назад.
 * Формат: массив { from: Date, to: Date }
 */
const loadedRanges = ref([]);

const colorPicker = ref({
  visible: false,
  group: null,
  currentColor: "#10B981",
  x: 0,
  y: 0,
});

// ─── Computed ─────────────────────────────────────────────────────────────────
const conflictedGroups = computed(() =>
  groups.value.filter((g) => Array.isArray(g.conflict_ids) && g.conflict_ids.length)
);

const groupsById = computed(() => {
  const map = {};
  groups.value.forEach((g) => (map[g.id] = g));
  return map;
});

/**
 * Вычисляем текущий видимый диапазон дат (from/to) с учётом буфера.
 * Используется для определения, какие данные запрашивать у API.
 */
const currentRange = computed(() => {
  const cfg = SCALES[scale.value];
  const from = addDays(viewStart.value, -cfg.bufferDays);
  const to = addDays(viewStart.value, cfg.rangeDays + cfg.bufferDays);
  return { from, to };
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
function hexAlpha(hex, opacity) {
  const alpha = Math.round(opacity * 255)
    .toString(16)
    .padStart(2, "0");
  return (hex.startsWith("#") ? hex : "#" + hex) + alpha;
}

/**
 * Затемняет HEX-цвет на заданный процент (0..1).
 * Используется для прогресс-бара — более тёмный оттенок цвета группы.
 */
function darkenHex(hex, amount = 0.25) {
  const h = hex.replace("#", "");
  const r = Math.max(0, Math.round(parseInt(h.substring(0, 2), 16) * (1 - amount)));
  const g = Math.max(0, Math.round(parseInt(h.substring(2, 4), 16) * (1 - amount)));
  const b = Math.max(0, Math.round(parseInt(h.substring(4, 6), 16) * (1 - amount)));
  return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b.toString(16).padStart(2, "0")}`;
}

/**
 * Конвертирует элемент API в формат задачи DHTMLX Gantt.
 */
function apiItemToTask(item) {
  return {
    id: item.id,
    text: item.text || "—",
    start_date: new Date(item.start_date + "T00:00:00"),
    end_date: new Date(item.end_date + "T23:59:59"),
    progress: (item.progress_percent || 0) / 100,
    color: item.color || "#10B981",
    course_code: item.course_code,
    status_label: item.status_label,
    participant_count: item.participant_count,
    total_cost: item.total_cost,
    price_per_person: item.price_per_person,
    conflict_ids: item.conflict_ids || [],
    duration: item.duration,
    api_start: item.start_date,
    api_end: item.end_date,
  };
}

/**
 * Проверяет, покрывается ли диапазон [from, to] уже загруженными диапазонами.
 * Если да — нет необходимости делать новый API-запрос.
 */
function isRangeCovered(from, to) {
  for (const range of loadedRanges.value) {
    if (range.from <= from && range.to >= to) {
      return true;
    }
  }
  return false;
}

/**
 * Добавляет загруженный диапазон в список и пытается смержить перекрывающиеся.
 */
function addLoadedRange(from, to) {
  loadedRanges.value.push({ from, to });

  // Сортируем и мержим пересекающиеся диапазоны
  loadedRanges.value.sort((a, b) => a.from - b.from);
  const merged = [];
  for (const range of loadedRanges.value) {
    if (merged.length === 0 || merged[merged.length - 1].to < range.from) {
      merged.push({ ...range });
    } else {
      merged[merged.length - 1].to = new Date(
        Math.max(merged[merged.length - 1].to.getTime(), range.to.getTime())
      );
    }
  }
  loadedRanges.value = merged;
}

/**
 * Собирает видимые группы из кеша на основе текущего диапазона.
 * Группа видима, если её период пересекается с текущим окном.
 */
function collectVisibleGroups() {
  const { from, to } = currentRange.value;
  const fromStr = format(from, "yyyy-MM-dd");
  const toStr = format(to, "yyyy-MM-dd");

  const visible = [];
  for (const g of groupsCache.values()) {
    // Пересечение: группа видна, если start_date <= to И end_date >= from
    if (g.start_date <= toStr && g.end_date >= fromStr) {
      visible.push(g);
    }
  }
  groups.value = visible;
}

// ─── DHTMLX Gantt Configuration ───────────────────────────────────────────────
function configureGantt() {
  // Локаль: русский
  gantt.locale = {
    date: {
      month_full: [
        "Январь","Февраль","Март","Апрель","Май","Июнь",
        "Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь",
      ],
      month_short: [
        "Янв","Фев","Мар","Апр","Май","Июн",
        "Июл","Авг","Сен","Окт","Ноя","Дек",
      ],
      day_full: [
        "Воскресенье","Понедельник","Вторник","Среда",
        "Четверг","Пятница","Суббота",
      ],
      day_short: ["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],
    },
    labels: {
      new_task: "Новая группа",
      dhx_cal_today_button: "Сегодня",
      day_tab: "День",
      week_tab: "Неделя",
      month_tab: "Месяц",
      new_event: "Новое",
      icon_save: "Сохранить",
      icon_cancel: "Отмена",
      icon_details: "Детали",
      icon_edit: "Редактировать",
      icon_delete: "Удалить",
      gantt_save_btn: "Сохранить",
      gantt_cancel_btn: "Отмена",
      gantt_delete_btn: "Удалить",
      confirm_closing: "",
      confirm_deleting: "Удалить запись?",
      section_description: "Описание",
      section_time: "Период",
      section_type: "Тип",
      column_wbs: "WBS",
      column_text: "Группа",
      column_start_date: "Начало",
      column_duration: "Длительность",
      column_add: "",
    },
  };

  // Общие настройки
  gantt.config.date_format = "%Y-%m-%d %H:%i:%s";
  gantt.config.fit_tasks = false;
  gantt.config.row_height = 44;
  gantt.config.bar_height = 28;
  gantt.config.scale_height = 54;
  gantt.config.min_column_width = 40;
  gantt.config.auto_scheduling = false;
  gantt.config.open_tree_initially = true;
  gantt.config.show_progress = true;
  gantt.config.drag_progress = false;
  gantt.config.drag_resize = true;
  gantt.config.drag_move = true;
  gantt.config.drag_links = false;
  gantt.config.details_on_dblclick = false;
  gantt.config.show_links = false;
  gantt.config.readonly = false;
  gantt.config.autofit = false;

  // Маркер «сегодня» и тултипы
  gantt.plugins({ marker: true, tooltip: true, quick_info: false });

  gantt.addMarker({
    start_date: new Date(),
    css: "gantt-today-marker",
    text: "Сегодня",
  });

  // ── Левая grid-панель: колонки ──────────────────────────────────────────────
  gantt.config.columns = [
    {
      name: "text",
      label: "Группа",
      tree: false,
      width: 220,
      resize: true,
      template: (task) => {
        const color = task.color || "#10B981";
        const hasConflict =
          Array.isArray(task.conflict_ids) && task.conflict_ids.length > 0;
        const conflictBadge = hasConflict
          ? `<span class="gantt-conflict-badge" title="Конфликт расписания">⚠️</span>`
          : "";
        return `
          <div class="gantt-cell-group">
            <span class="gantt-cell-dot gantt-color-btn" data-task-id="${task.id}" style="background:${color}" title="Изменить цвет"></span>
            <div class="gantt-cell-text">
              <span class="gantt-cell-name">${task.text}</span>
              <span class="gantt-cell-meta">${task.participant_count || 0} уч. · ${Math.round((task.progress || 0) * 100)}%</span>
            </div>
            ${conflictBadge}
          </div>
        `;
      },
    },
    {
      name: "start_date",
      label: "Начало",
      align: "center",
      width: 90,
      resize: true,
    },
    {
      name: "duration",
      label: "Дни",
      align: "center",
      width: 50,
      resize: true,
      template: (task) => task.duration || "—",
    },
  ];

  // ── Масштабы: zoom levels ───────────────────────────────────────────────────
  gantt.ext.zoom.init({
    levels: [
      {
        name: "week",
        scale_height: 54,
        min_column_width: 50,
        scales: [
          { unit: "month", step: 1, format: "%F %Y" },
          { unit: "day", step: 1, format: "%d %D" },
        ],
      },
      {
        name: "month",
        scale_height: 54,
        min_column_width: 40,
        scales: [
          { unit: "month", step: 1, format: "%F %Y" },
          { unit: "week", step: 1, format: "Нед %W" },
        ],
      },
      {
        name: "quarter",
        scale_height: 54,
        min_column_width: 30,
        scales: [
          { unit: "quarter", step: 1, format: (date) => {
            const q = Math.ceil((date.getMonth() + 1) / 3);
            return `Q${q} ${date.getFullYear()}`;
          }},
          { unit: "month", step: 1, format: "%M" },
        ],
      },
    ],
  });

  // ── Кастомный шаблон бара ───────────────────────────────────────────────────
  gantt.templates.task_class = (start, end, task) => {
    const classes = ["gantt-custom-bar"];
    if (Array.isArray(task.conflict_ids) && task.conflict_ids.length > 0) {
      classes.push("gantt-bar-conflict");
    }
    return classes.join(" ");
  };

  gantt.templates.timeline_cell_class = () => "";

  gantt.templates.task_text = (start, end, task) => {
    return `<span class="gantt-bar-text">${task.text}</span>`;
  };

  gantt.templates.progress_text = () => "";
  gantt.templates.task_row_class = () => "gantt-custom-row";

  // ── Tooltip ─────────────────────────────────────────────────────────────────
  gantt.templates.tooltip_text = (start, end, task) => {
    const cost = Number(task.total_cost || 0).toLocaleString("ru-RU");
    const pct = Math.round((task.progress || 0) * 100);
    const hasConflict =
      Array.isArray(task.conflict_ids) && task.conflict_ids.length > 0;
    const conflictHtml = hasConflict
      ? `<div class="gantt-tip-conflict">⚠️ Конфликт с ID: ${task.conflict_ids.join(", ")}</div>`
      : "";

    return `
      <div class="gantt-tooltip-card">
        <div class="gantt-tip-header" style="border-left: 4px solid ${task.color}; background: ${hexAlpha(task.color, 0.08)}">
          <div class="gantt-tip-title">${task.text}</div>
          <div class="gantt-tip-sub">${task.course_code || ""} · ${task.status_label || ""}</div>
        </div>
        <div class="gantt-tip-body">
          <div class="gantt-tip-row"> ${task.api_start} — ${task.api_end} <span class="gantt-tip-badge">${task.duration || "?"} дн.</span></div>
          <div class="gantt-tip-row"> ${task.participant_count || 0} участников</div>
          <div class="gantt-tip-row"> ${cost} ₽</div>
          <div class="gantt-tip-progress">
            <div class="gantt-tip-progress-label">
              <span>Прогресс</span><span>${pct}%</span>
            </div>
            <div class="gantt-tip-progress-bar">
              <div class="gantt-tip-progress-fill" style="width:${pct}%; background:${darkenHex(task.color, 0.25)}"></div>
            </div>
          </div>
          ${conflictHtml}
        </div>
      </div>
    `;
  };

  // ── Подавляем тултип, когда color picker открыт ─────────────────────────────
  gantt.attachEvent("onBeforeTooltip", (id, mode, e) => {
  if (colorPicker.value.visible) return false;

  // Показываем тултип ТОЛЬКО если курсор над баром в timeline-части.
  // Исправляет баг: тултип оставался при переходе курсора на левую панель.
  const target = e?.target || e?.srcElement;
  if (!target) return false;

  const isOnBar =
    target.closest(".gantt_task_line") ||
    target.closest(".gantt_task_drag") ||
    target.closest(".gantt_task_progress_drag");

  return !!isOnBar;
  });

  // Скрываем тултип при уходе в grid-панель (левая часть)
  gantt.attachEvent("onMouseMove", (id, e) => {
    if (!e) return;
    const target = e.target || e.srcElement;
    if (!target) return;

    const inTaskArea =
      target.closest(".gantt_task_line") ||
      target.closest(".gantt_task_area") ||
      target.closest(".gantt_task_row") ||
      target.closest(".gantt_row") ||
      target.closest(".gantt_row_task");

    const tooltipEl = document.querySelector(".gantt_tooltip");
    if (tooltipEl) {
      tooltipEl.style.visibility = inTaskArea ? "" : "hidden";
    }
  });

  // ── Обработка drag-and-drop: PATCH даты ─────────────────────────────────────
  gantt.attachEvent("onAfterTaskDrag", async (id, mode) => {
    const task = gantt.getTask(id);
    const newStart = format(task.start_date, "yyyy-MM-dd");
    const newEnd = format(task.end_date, "yyyy-MM-dd");

    try {
      await apiClient.patch(`/gantt/${id}/dates`, {
        start_date: newStart,
        end_date: newEnd,
      });
      // Инвалидируем кеш и перезагружаем текущий диапазон —
      // conflict_ids пересчитываются сервером
      invalidateCache();
      await loadGantt();
    } catch (e) {
      console.error("Ошибка обновления дат:", e);
      invalidateCache();
      await loadGantt();
    }
  });

  // ── Клик по бару ─────────────────────────────────────────────────────────────
  gantt.attachEvent("onTaskClick", (id, e) => {
    return true;
  });

  gantt.attachEvent("onTaskDblClick", (id) => {
    router.push({ name: "training-groups.show", params: { id } });
    return false;
  });

  // Правый клик по бару → color picker
  gantt.attachEvent("onContextMenu", (taskId, linkId, e) => {
    if (taskId) {
      e.preventDefault();
      const local = groupsById.value[taskId];
      if (local) openColorPicker(e, local);
      return false;
    }
    return true;
  });
}

// ─── Cache Management ─────────────────────────────────────────────────────────

/**
 * Полностью сбрасывает кеш.
 * Вызывается после drag-and-drop и других мутаций,
 * когда серверные данные (conflict_ids и т.д.) могли измениться.
 */
function invalidateCache() {
  groupsCache.clear();
  loadedRanges.value = [];
}

// ─── Init / Refresh Gantt ─────────────────────────────────────────────────────
function initGantt() {
  if (!ganttContainer.value) return;

  configureGantt();
  gantt.init(ganttContainer.value);
  ganttContainer.value.addEventListener("mouseleave", () => {
    const tooltipEl = document.querySelector(".gantt_tooltip");
    if (tooltipEl) tooltipEl.style.visibility = "hidden";
  });

  ganttReady.value = true;

  gantt.ext.zoom.setLevel(SCALES[scale.value].zoomLevel);

  // Обработчик клика по цветному кружку в grid-панели
  ganttContainer.value.addEventListener("click", (e) => {
    const dot = e.target.closest(".gantt-color-btn");
    if (dot) {
      const taskId = Number(dot.dataset.taskId);
      const local = groupsById.value[taskId];
      if (local) {
        e.stopPropagation();
        openColorPicker(e, local);
      }
    }
  });
}

function refreshGanttData() {
  if (!ganttReady.value) return;

  gantt.clearAll();

  // Динамический диапазон шкалы: от текущего окна с запасом
  const cfg = SCALES[scale.value];
  gantt.config.start_date = addDays(viewStart.value, -(cfg.bufferDays + 30));
  gantt.config.end_date = addDays(viewStart.value, cfg.rangeDays + cfg.bufferDays + 30);

  const tasks = groups.value.map(apiItemToTask);

  gantt.parse({
    data: tasks,
    links: [],
  });

  // Красим бары через gantt API
  groups.value.forEach((g) => {
    try {
      const task = gantt.getTask(g.id);
      if (task) {
        task.color = g.color || "#10B981";
        task.progressColor = g.color || "#10B981";
      }
    } catch {
      // ignore
    }
  });

  gantt.render();

  requestAnimationFrame(() => applyBarColors());
}

function applyBarColors() {
  groups.value.forEach((g) => {
    try {
      const node = gantt.getTaskNode(g.id);
      if (!node) return;
      const color = g.color || "#10B981";

      node.style.background = hexAlpha(color, 0.15);
      node.style.border = `1.5px solid ${color}`;
      node.style.borderRadius = "8px";
      node.style.overflow = "hidden";

      const progress = node.querySelector(".gantt_task_progress");
      if (progress) {
        progress.style.background = darkenHex(color, 0.25);
        progress.style.opacity = "0.9";
        progress.style.borderRadius = "8px";
      }

      const hasConflict = Array.isArray(g.conflict_ids) && g.conflict_ids.length > 0;
      if (hasConflict) {
        node.style.outline = "2px dashed hsl(0 84% 60%)";
        node.style.outlineOffset = "1px";
      } else {
        node.style.outline = "none";
        node.style.outlineOffset = "0";
      }
    } catch {
      // ignore
    }
  });
}

// ─── API: Load (с кешированием по диапазонам) ─────────────────────────────────
let isFirstLoad = true;

/**
 * Загружает данные для текущего видимого диапазона.
 *
 * Логика кеширования:
 *   1. Вычисляем from/to с учётом буфера
 *   2. Если этот диапазон уже покрыт ранее загруженными данными — берём из кеша
 *   3. Иначе — запрашиваем API, мержим в кеш
 *   4. Собираем видимые группы из кеша
 *
 * @param {Object} options
 * @param {boolean} options.silent — не показывать лоадер (для фоновых обновлений)
 * @param {boolean} options.force — принудительно запросить API, игнорируя кеш
 */
async function loadGantt({ silent = false, force = false } = {}) {
  const { from, to } = currentRange.value;
  const fromStr = format(from, "yyyy-MM-dd");
  const toStr = format(to, "yyyy-MM-dd");

  // Если диапазон уже покрыт и нет принудительного обновления — берём из кеша
  if (!force && isRangeCovered(from, to)) {
    collectVisibleGroups();
    await nextTick();
    refreshGanttData();
    return;
  }

  if (!silent) loading.value = true;

  try {
    const { data } = await apiClient.get("/gantt", {
      params: {
        from: fromStr,
        to: toStr,
      },
    });

    const items = data?.data?.items || [];

    if (data?.data?.palette?.length) {
      palette.value = data.data.palette;
    }

    // Мержим в кеш — обновляем существующие, добавляем новые
    items.forEach((item) => {
      groupsCache.set(item.id, item);
    });

    // Запоминаем загруженный диапазон
    addLoadedRange(from, to);

    // При первой загрузке — центрируемся на данных
    if (isFirstLoad && data?.data?.period?.from && items.length > 0) {
      viewStart.value = addDays(parseISO(data.data.period.from), -2);
      isFirstLoad = false;
    }

    // Собираем видимые группы из кеша
    collectVisibleGroups();
  } catch (e) {
    console.error("Ошибка загрузки gantt:", e);
    groups.value = [];
  } finally {
    if (!silent) loading.value = false;
    await nextTick();
    refreshGanttData();
  }
}

// ─── Navigation ───────────────────────────────────────────────────────────────
/**
 * Навигация стрелками: сдвигает viewport и подгружает данные при необходимости.
 */
async function navigate(dir) {
  viewStart.value = addDays(viewStart.value, dir * SCALES[scale.value].navDays);

  // Загружаем данные для нового диапазона (из кеша или API)
  await loadGantt({ silent: true });

  if (ganttReady.value) {
    gantt.showDate(viewStart.value);
  }
}

function goToday() {
  viewStart.value = new Date();
  loadGantt({ silent: true }).then(() => {
    if (ganttReady.value) {
      gantt.showDate(new Date());
    }
  });
}

// ─── Scale switching ──────────────────────────────────────────────────────────
watch(scale, async (newScale) => {
  if (ganttReady.value) {
    gantt.ext.zoom.setLevel(SCALES[newScale].zoomLevel);

    // При смене масштаба диапазон окна меняется — возможно нужна подгрузка
    await loadGantt({ silent: true });

    gantt.render();
    requestAnimationFrame(() => {
      applyBarColors();
      gantt.showDate(viewStart.value);
    });
  }
});

// ─── Export ───────────────────────────────────────────────────────────────────
async function exportGantt() {
  exporting.value = true;
  try {
    const from = format(viewStart.value, "yyyy-MM-dd");
    const to = format(
      addDays(viewStart.value, SCALES[scale.value].rangeDays),
      "yyyy-MM-dd"
    );

    const response = await apiClient.get("/gantt/export", {
      params: { from, to, format: "csv" },
      responseType: "blob",
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `gantt_export_${from}_${to}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Ошибка экспорта:", e);
  } finally {
    exporting.value = false;
  }
}

// ─── Color Picker ─────────────────────────────────────────────────────────────
function openColorPicker(event, group) {
  // Скрываем тултип Ганта, чтобы он не перекрывал picker
  gantt.ext.tooltips?.tooltip?.hide?.();
  const tooltipEl = document.querySelector(".gantt_tooltip");
  if (tooltipEl) tooltipEl.style.display = "none";

  const PICKER_W = 220;
  const PICKER_H = 170;
  let x = (event.clientX || event.pageX || 100) + 8;
  let y = (event.clientY || event.pageY || 100) + 8;
  if (x + PICKER_W > window.innerWidth) x = x - PICKER_W - 16;
  if (y + PICKER_H > window.innerHeight) y = y - PICKER_H - 16;

  colorPicker.value = {
    visible: true,
    group,
    currentColor: group.color || "#10B981",
    x,
    y,
  };
}

async function applyColor(color) {
  const id = colorPicker.value.group?.id;
  if (!id) return;

  closeColorPicker();

  // 1. Обновляем кеш
  if (groupsCache.has(id)) {
    groupsCache.get(id).color = color;
  }

  // 2. Обновляем реактивный массив — заменяем объект целиком (новая ссылка → Vue видит изменение)
  const idx = groups.value.findIndex((g) => g.id === id);
  if (idx !== -1) {
    groups.value[idx] = { ...groups.value[idx], color };
  }

  // 3. Обновляем задачу в DHTMLX — refreshTask перерисует grid-ячейку (gantt-cell-dot)
  //    и обновит task-объект, который читает tooltip_text
  try {
    const task = gantt.getTask(id);
    if (task) {
      task.color = color;
      task.progressColor = color;
      gantt.refreshTask(id);
    }
  } catch {
    // задача может отсутствовать
  }

  // 4. DOM-патч бара (фон + прогресс) — после того как refreshTask обновил DOM
  requestAnimationFrame(() => {
    try {
      const node = gantt.getTaskNode(id);
      if (!node) return;

      node.style.background = hexAlpha(color, 0.15);
      node.style.border = `1.5px solid ${color}`;

      const progress = node.querySelector(".gantt_task_progress");
      if (progress) {
        progress.style.background = darkenHex(color, 0.25);
      }
    } catch {
      // ignore
    }
  });

  // 5. PATCH на сервер
  try {
    await apiClient.patch(`/gantt/${id}/color`, { color });
  } catch (e) {
    console.error("Ошибка обновления цвета:", e);
    invalidateCache();
    await loadGantt();
  }
}

function closeColorPicker() {
  colorPicker.value.visible = false;
}

function onKeydown(e) {
  if (e.key === "Escape") {
    closeColorPicker();
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  document.addEventListener("keydown", onKeydown);
  await loadGantt();
  await nextTick();
  initGantt();
  refreshGanttData();
  if (ganttReady.value) {
    gantt.showDate(viewStart.value);
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("keydown", onKeydown);
  if (ganttReady.value) {
    gantt.clearAll();
  }
});
</script>

<style>
/*
 * ============================================================================
 * DHTMLX Gantt — кастомные стили, интегрированные с shadcn/ui CSS-переменными
 * ============================================================================
 */

/* ── Общий контейнер ───────────────────────────────────────────────────────── */
.gantt-outer {
  container-type: inline-size;
}

.gantt-dhtmlx-target {
  --gantt-font: inherit;
}

.gantt-dhtmlx-target .gantt_container {
  border: none !important;
  background: hsl(var(--card)) !important;
  font-family: inherit !important;
}

/* ── Grid (левая панель) ───────────────────────────────────────────────────── */
.gantt-dhtmlx-target .gantt_grid {
  background: hsl(var(--card)) !important;
  border-right: 1px solid hsl(var(--border)) !important;
}

.gantt-dhtmlx-target .gantt_grid_head_cell {
  color: hsl(var(--muted-foreground)) !important;
  font-size: 11px !important;
  font-weight: 600 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important;
  border-bottom: 1px solid hsl(var(--border)) !important;
  background: hsl(var(--muted) / 0.5) !important;
}

.gantt-dhtmlx-target .gantt_grid_data .gantt_cell {
  border-bottom: 1px solid hsl(var(--border) / 0.5) !important;
  color: hsl(var(--foreground)) !important;
  font-size: 13px !important;
}

.gantt-dhtmlx-target .gantt_row {
  border-bottom: 1px solid hsl(var(--border) / 0.5) !important;
  background: hsl(var(--card)) !important;
}

.gantt-dhtmlx-target .gantt_row:hover {
  background: hsl(var(--muted) / 0.3) !important;
}

/* ── Кастомная ячейка группы ───────────────────────────────────────────────── */
.gantt-cell-group {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 0;
  width: 100%;
}

.gantt-cell-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.gantt-color-btn {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  border: 2px solid transparent;
}

.gantt-color-btn:hover {
  transform: scale(1.4);
  box-shadow: 0 0 0 2px hsl(var(--background)), 0 0 0 4px hsl(var(--border));
}

.gantt-cell-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.gantt-cell-name {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: hsl(var(--foreground));
}

.gantt-cell-meta {
  font-size: 11px;
  color: hsl(var(--muted-foreground));
  line-height: 1.3;
}

.gantt-conflict-badge {
  flex-shrink: 0;
  font-size: 12px;
}

/* ── Scale / Timeline header ───────────────────────────────────────────────── */
.gantt-dhtmlx-target .gantt_scale_line {
  border-bottom: 1px solid hsl(var(--border)) !important;
  background: hsl(var(--muted) / 0.5) !important;
}

.gantt-dhtmlx-target .gantt_scale_cell {
  color: hsl(var(--muted-foreground)) !important;
  font-size: 12px !important;
  border-right: 1px solid hsl(var(--border) / 0.4) !important;
}

/* ── Task area / timeline rows ─────────────────────────────────────────────── */
.gantt-dhtmlx-target .gantt_task_row {
  border-bottom: 1px solid hsl(var(--border) / 0.3) !important;
  background: transparent !important;
}

.gantt-dhtmlx-target .gantt_task_row:hover {
  background: hsl(var(--muted) / 0.15) !important;
}

.gantt-dhtmlx-target .gantt_task_cell {
  border-right: 1px solid hsl(var(--border) / 0.15) !important;
}

/* ── Task bars ─────────────────────────────────────────────────────────────── */
.gantt-dhtmlx-target .gantt_task_line {
  border-radius: 8px !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
  transition: box-shadow 0.2s ease, transform 0.1s ease !important;
}

.gantt-dhtmlx-target .gantt_task_line:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  transform: translateY(-1px);
}

.gantt-dhtmlx-target .gantt_task_progress {
  border-radius: 8px !important;
}

.gantt-bar-text {
  font-size: 12px;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin-right: 6px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.gantt-bar-pct {
  font-size: 11px;
  font-weight: 600;
  opacity: 0;
  color:gray;
}

/* ── Конфликтный бар: пульсация ────────────────────────────────────────────── */
.gantt-bar-conflict .gantt_task_line {
  animation: conflict-pulse 1.5s ease-in-out infinite alternate !important;
}

@keyframes conflict-pulse {
  from {
    outline: 2px dashed hsl(0 84% 60%);
    outline-offset: 1px;
  }
  to {
    outline: 2px dashed hsl(0 84% 60% / 0.3);
    outline-offset: 1px;
  }
}

/* ── Today marker ──────────────────────────────────────────────────────────── */
.gantt-today-marker {
  background: hsl(var(--primary)) !important;
  opacity: 0.6;
  width: 2px !important;
}

.gantt-today-marker .gantt_marker_content {
  font-size: 11px;
  color: hsl(var(--primary));
  font-weight: 600;
  background: hsl(var(--primary) / 0.1);
  border-radius: 4px;
  padding: 2px 6px;
}

/* ── Resize handles ────────────────────────────────────────────────────────── */
.gantt-dhtmlx-target .gantt_task_drag {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.gantt-dhtmlx-target .gantt_task_line:hover .gantt_task_drag {
  opacity: 0.5;
}

/* ── Tooltip ───────────────────────────────────────────────────────────────── */
  .gantt-dhtmlx-target .gantt_tooltip,
  .gantt_tooltip {
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    opacity: 1 !important;
    pointer-events: none;
  }

.gantt-tooltip-card {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  overflow: hidden;
  min-width: 240px;
  max-width: 320px;
  font-family: inherit;
  color: hsl(var(--foreground));
}

.gantt-tip-header {
  padding: 10px 14px;
}

.gantt-tip-title {
  font-weight: 600;
  font-size: 13px;
  line-height: 1.4;
}

.gantt-tip-sub {
  font-size: 11px;
  color: hsl(var(--muted-foreground));
  margin-top: 2px;
}

.gantt-tip-body {
  padding: 10px 14px;
}

.gantt-tip-row {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}

.gantt-tip-badge {
  font-size: 10px;
  background: hsl(var(--muted));
  padding: 1px 6px;
  border-radius: 4px;
  margin-left: 4px;
}

.gantt-tip-progress {
  margin-top: 8px;
}

.gantt-tip-progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: hsl(var(--muted-foreground));
  margin-bottom: 4px;
}

.gantt-tip-progress-label span:last-child {
  font-weight: 600;
  color: hsl(var(--foreground));
}

.gantt-tip-progress-bar {
  height: 6px;
  background: hsl(var(--muted));
  border-radius: 3px;
  overflow: hidden;
}

.gantt-tip-progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.gantt-tip-conflict {
  margin-top: 8px;
  font-size: 11px;
  color: hsl(0 84% 60%);
  background: hsl(0 84% 60% / 0.08);
  padding: 6px 10px;
  border-radius: 6px;
}

/* ── Scrollbar styling ─────────────────────────────────────────────────────── */
.gantt-dhtmlx-target .gantt_container::-webkit-scrollbar,
.gantt-dhtmlx-target .gantt_hor_scroll::-webkit-scrollbar,
.gantt-dhtmlx-target .gantt_ver_scroll::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.gantt-dhtmlx-target .gantt_container::-webkit-scrollbar-thumb,
.gantt-dhtmlx-target .gantt_hor_scroll::-webkit-scrollbar-thumb,
.gantt-dhtmlx-target .gantt_ver_scroll::-webkit-scrollbar-thumb {
  background: hsl(var(--muted-foreground) / 0.3);
  border-radius: 4px;
}

/* ── Popup fade transition ─────────────────────────────────────────────────── */
.popup-fade-enter-active,
.popup-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.popup-fade-enter-from,
.popup-fade-leave-to {
  opacity: 0;
  transform: scale(0.96) translateY(-4px);
}

.color-input::-webkit-color-swatch-wrapper { padding: 0; }
.color-input::-webkit-color-swatch { border-radius: 50%; border: none; }
</style>