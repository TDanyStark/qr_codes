import { create } from 'zustand'

export type User = {
  id: number
  username: string
  firstName: string
  lastName: string
}

type UsersState = {
  users: User[]
  // fetch/async state handled by react-query; store keeps local list if needed
  setUsers: (users: User[]) => void
}

export const useUsersStore = create<UsersState>((set: (partial: Partial<UsersState> | ((state: UsersState) => Partial<UsersState>)) => void) => ({
  users: [],
  setUsers: (users: User[]) => set({ users }),
}))

export default useUsersStore
