export interface Task {
  id: number;
  title: string;
  status: 'todo' | 'doing' | 'done';
  sort_order: number;
  created_at?: string;
}
