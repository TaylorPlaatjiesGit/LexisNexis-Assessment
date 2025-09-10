import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { DocumentService } from '../../services/document.service';
import { MatCardModule } from '@angular/material/card';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';

@Component({
  selector: 'app-document-list',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    MatCardModule,
    MatListModule,
    MatIconModule,
    MatButtonModule,
    MatProgressSpinnerModule,
    MatPaginatorModule
  ],
  templateUrl: './document-list.component.html',
  styleUrl: './document-list.component.scss'
})
export class DocumentListComponent implements OnInit {
  documents: any[] = [];
  totalItems = 0;

  pageSize = 10;
  pageIndex = 0;
  loading = true;

  constructor(private docService: DocumentService) {}

  ngOnInit(): void {
    this.fetchDocuments();
  }

  fetchDocuments(): void {
    this.loading = true;

    const offset = this.pageIndex * this.pageSize;

    this.docService.getDocuments(this.pageIndex, this.pageSize).subscribe({
      next: (res: any) => {
        this.documents = res.documents ?? [];
        this.totalItems = res.totalCount ?? this.documents.length;
        this.loading = false;
      },
      error: (err) => {
        console.error('Failed to load documents', err);
        this.loading = false;
      }
    });
  }

  onPageChange(event: PageEvent): void {
    this.pageIndex = event.pageIndex;
    this.pageSize = event.pageSize;
    this.fetchDocuments();
  }

  deleteDocument(id: number): void {
    if (confirm('Are you sure you want to delete this document?')) {
      this.docService.deleteDocument(id).subscribe(() => {
        this.fetchDocuments();
      });
    }
  }
}
