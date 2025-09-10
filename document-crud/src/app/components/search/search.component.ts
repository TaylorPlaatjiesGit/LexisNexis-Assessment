import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { DocumentService } from '../../services/document.service';
import { debounce } from 'lodash';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-search',
  imports: [CommonModule, FormsModule, MatCardModule, MatFormFieldModule, MatInputModule, MatListModule, RouterModule],
  templateUrl: './search.component.html',
  styleUrl: './search.component.scss'
})
export class SearchComponent {
  query = '';
  results: any[] = [];
  timeTaken = 0;

  constructor(private docService: DocumentService) {}

  get tokens(): string[] {
    return this.query.trim().toLowerCase().split(/\s+/).filter(Boolean);
  }

  onSearchChange = debounce(() => {
    const start = performance.now();
    this.docService.searchDocuments(this.query).subscribe((res: any) => {
      this.results = res;
      this.timeTaken = performance.now() - start;
    });
  }, 300);
}
