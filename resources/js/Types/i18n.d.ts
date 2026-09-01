import 'vue'

declare module 'vue' {
  interface ComponentCustomProperties {
    $t: (key: string, ...args: any[]) => string;
    $tc: (key: string, choice?: number, ...args: any[]) => string;
  }
}
