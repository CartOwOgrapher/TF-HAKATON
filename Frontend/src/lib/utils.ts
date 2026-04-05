import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Объединение Tailwind классов
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * clsx типы (чтобы TS не ругался)
 */
type ClassValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | Record<string, any>
  | ClassValue[];

/**
 * Проверка iframe (без падений в SSR / build)
 */
export const isIframe: boolean =
  typeof window !== "undefined" &&
  typeof window.self !== "undefined" &&
  typeof window.top !== "undefined"
    ? window.self !== window.top
    : false;