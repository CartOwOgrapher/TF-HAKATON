<template>
  <aside
    :class="[
      'fixed left-0 top-0 h-screen bg-sidebar text-sidebar-foreground flex flex-col z-50 transition-all duration-300 border-r border-sidebar-border',
      collapsed ? 'w-[72px]' : 'w-[260px]'
    ]"
  >
    <!-- Logo -->
    <div
      class="flex items-center gap-3 px-5 h-16 border-b border-sidebar-border shrink-0"
    >
      <div
        class="w-9 h-9 rounded-xl bg-sidebar-primary flex items-center justify-center shrink-0"
      >
        <GraduationCap class="w-5 h-5 text-sidebar-primary-foreground" />
      </div>

      <div v-if="!collapsed" class="overflow-hidden">
        <h1 class="text-sm font-semibold tracking-tight truncate">
          Обучение
        </h1>
        <p class="text-[10px] text-sidebar-foreground/50 uppercase tracking-widest">
          Управление
        </p>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
      <router-link
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
        :class="isActive(item.path)"
      >
        <component :is="item.icon" class="w-5 h-5 shrink-0" />
        <span v-if="!collapsed" class="truncate">
          {{ item.label }}
        </span>
      </router-link>
    </nav>

    <!-- Bottom actions -->
    <div class="px-3 mb-2 space-y-1">
      <!-- Logout -->
      <button
        @click="handleLogout"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-sidebar-foreground/70 hover:bg-red-500/10 hover:text-red-400 transition-all duration-200"
      >
        <LogOut class="w-5 h-5 shrink-0" />
        <span v-if="!collapsed" class="truncate">Выйти</span>
      </button>

      <!-- Collapse -->
      <button
        @click="toggle"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs text-sidebar-foreground/50 hover:bg-sidebar-accent transition-colors"
      >
        <ChevronRight v-if="collapsed" class="w-4 h-4" />
        <template v-else>
          <ChevronLeft class="w-4 h-4" />
          <span>Свернуть</span>
        </template>
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/useAuthStore";

import {
  LayoutDashboard,
  BookOpen,
  Users,
  UsersRound,
  Building2,
  FileText,
  GanttChart,
  GraduationCap,
  ChevronLeft,
  ChevronRight,
  LogOut,
  BarChart3,
  FileCode,
} from "lucide-vue-next";

const route = useRoute();
const authStore = useAuthStore();

const props = defineProps({
  collapsed: Boolean
});

const emit = defineEmits(["update:collapsed"]);

function toggle() {
  emit("update:collapsed", !props.collapsed);
}

async function handleLogout() {
  await authStore.logout();
}

const navItems = [
  { path: "/", label: "Главная", icon: LayoutDashboard },
  { path: "/courses", label: "Курсы", icon: BookOpen },
  { path: "/participants", label: "Участники", icon: Users },
  { path: "/companies", label: "Компании", icon: Building2 },
  { path: "/groups", label: "Группы", icon: UsersRound },
  { path: "/specifications", label: "Спецификации", icon: FileText },
  { path: "/gantt", label: "График Ганта", icon: GanttChart },
  { path: "/analytics", label: "Аналитика", icon: BarChart3 },
  { path: '/xml-import', label: 'XML Импорт', icon: FileCode },
];

function isActive(path: string) {
  return route.path === path
    ? "bg-sidebar-primary text-sidebar-primary-foreground shadow-lg shadow-sidebar-primary/20"
    : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground";
}
</script>
