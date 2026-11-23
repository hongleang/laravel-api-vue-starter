import type { Component } from 'vue';
import type { RouteLocationRaw } from 'vue-router';

import type { Permission } from './auth';

export interface BreadcrumbItem {
  title: string;
  href?: RouteLocationRaw;
}

export interface NavItem {
  title: string;
  href: RouteLocationRaw;
  icon: Component;
  /** Hide the item unless the user has this permission. */
  permission?: Permission;
}
