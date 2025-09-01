import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Task } from '../models/task.model';
import { environment } from '../environments/environment';

@Injectable({ providedIn: 'root' })
export class TaskService {
  private base = `${environment.apiUrl}/tasks`;

  constructor(private http: HttpClient) {}

  list(): Observable<Task[]> {
    return this.http.get<Task[]>(this.base);
  }

  create(payload: Partial<Task>): Observable<Task> {
    return this.http.post<Task>(this.base, payload);
  }

  update(id: number, payload: Partial<Task>): Observable<Task> {
    return this.http.put<Task>(`${this.base}/${id}`, payload);
  }

  remove(id: number): Observable<void> {
    return this.http.delete<void>(`${this.base}/${id}`);
  }

  reorder(status: Task['status'], orderedIds: number[]) {
    return this.http.patch(`${environment.apiUrl}/tasks/reorder`, { status, orderedIds });
  }
}
