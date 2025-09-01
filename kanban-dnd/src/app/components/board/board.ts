import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DragDropModule, CdkDragDrop, moveItemInArray, transferArrayItem } from '@angular/cdk/drag-drop';
import { TaskService } from '../../services/task.service';
import { Task } from '../../models/task.model';

@Component({
  selector: 'app-board',
  standalone: true,
  templateUrl: './board.html',
  styleUrls: ['./board.css'],
  imports: [CommonModule, FormsModule, DragDropModule]
})
export class BoardComponent implements OnInit {
  todo: Task[] = [];
  doing: Task[] = [];
  done: Task[] = [];
  newTitle = '';

  constructor(private api: TaskService) {}

  ngOnInit() { this.load(); }

  trackId = (_: number, t: Task) => t.id;

  load() {
    this.api.list().subscribe(rows => {
      this.todo  = rows.filter(r => r.status === 'todo').sort((a,b)=>a.sort_order-b.sort_order);
      this.doing = rows.filter(r => r.status === 'doing').sort((a,b)=>a.sort_order-b.sort_order);
      this.done  = rows.filter(r => r.status === 'done').sort((a,b)=>a.sort_order-b.sort_order);
    });
  }

  add() {
    const title = this.newTitle.trim();
    if (!title) return;
    this.api.create({ title, status: 'todo' }).subscribe(() => {
      this.newTitle = '';
      this.load();
    });
  }

  drop(event: CdkDragDrop<Task[]>, status: Task['status']) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
    } else {
      transferArrayItem(
        event.previousContainer.data,
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );
      // maj du status sur l'item déplacé
      event.container.data[event.currentIndex].status = status;
    }
    // persiste l'ordre de la colonne cible
    const ids = event.container.data.map(t => t.id);
    this.api.reorder(status, ids).subscribe();
  }

  remove(task: Task) {
    if (!confirm('Supprimer ?')) return;
    this.api.remove(task.id).subscribe(() => this.load());
  }
}
