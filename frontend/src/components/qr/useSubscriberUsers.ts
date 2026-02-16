import { useQuery } from "@tanstack/react-query";
import axios from "axios";

export type SubscriptionUser = {
  id: number;
  name: string;
  email: string;
  rol?: string;
};

async function fetchSubscriberUsers(): Promise<SubscriptionUser[]> {
  const token = localStorage.getItem("token");
  if (!token) {
    throw new Error("No auth token found");
  }

  const res = await axios.get("/api/users/subscribers", {
    headers: { Authorization: `Bearer ${token}` },
  });

  const body = res.data as { statusCode?: number; data?: SubscriptionUser[] };
  return body.data ?? [];
}

export default function useSubscriberUsers(enabled = true) {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["subscription-users"],
    queryFn: fetchSubscriberUsers,
    enabled,
  });

  return {
    users: data ?? [],
    loading: isLoading,
    error: isError ? error?.message ?? "Error fetching users" : null,
  } as const;
}
