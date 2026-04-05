import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";

import AppLayout from "../components/layout/AppLayout.vue";

import Dashboard from "../views/Dashboard.vue";
import Courses from "../views/Courses.vue";
import Companies from "../views/Companies.vue";
import Participants from "../views/Participants.vue";
import Groups from "../views/Groups.vue";
import GroupDetail from "../views/GroupDetail.vue";
import Specifications from "../views/Specifications.vue";
import GanttChart from "../views/GanttChart.vue";
import Analytics from "../views/Analytics.vue";
import Login from "../views/Login.vue";
import Register from "../views/Register.vue";
import XmlImport from "../views/XmlImportPage.vue";

import { useAuthStore } from "@/stores/useAuthStore";

const routes: Array<RouteRecordRaw> = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { guest: true },
  },
  {
    path: "/register",
    name: "Register",
    component: Register,
    meta: { guest: true },
  },
  {
    path: "/",
    component: AppLayout,
    children: [
      { path: "", name: "Dashboard", component: Dashboard },
      { path: "courses", name: "Courses", component: Courses },
      { path: "companies", name: "Companies", component: Companies },
      { path: "participants", name: "Participants", component: Participants },
      { path: "groups", name: "Groups", component: Groups },
      { path: "groups/:id", name: "GroupDetail", component: GroupDetail, props: true },
      { path: "specifications", name: "Specifications", component: Specifications },
      { path: "gantt", name: "GanttChart", component: GanttChart },
      { path: "analytics", name: "Analytics", component: Analytics },
      { path: '/xml-import',name: 'XmlImport',component: XmlImport}
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  const authStore = useAuthStore();

  if (to.meta.guest && authStore.isAuthenticated) {
    return '/';
  }

  if (!to.meta.guest && !authStore.isAuthenticated) {
    return '/login';
  }
});

export default router;
