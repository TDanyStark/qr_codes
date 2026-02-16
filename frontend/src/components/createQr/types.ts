export interface QrFormData {
  target_url: string;
  name: string;
  foreground: string;
  background: string;
  subscriber_user_ids: number[];
}

export interface QrLinks {
  png?: string | null;
  svg?: string | null;
  redirect?: string | null;
}

export const defaultFormData: QrFormData = {
  target_url: "",
  name: "",
  foreground: "#000000",
  background: "#ffffff",
  subscriber_user_ids: [],
};
