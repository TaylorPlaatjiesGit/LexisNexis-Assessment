import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class DocumentService {
  private apiUrl = 'http://assessment.test/api.php';

  constructor(private http: HttpClient) {}

  uploadDocument(file: File, description: string): Observable<any> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('description', description);
    return this.http.post(`${this.apiUrl}?request=documents`, formData);
  }

  getDocuments(pageIndex: number, pageSize: number): Observable<any> {
    const params = new HttpParams()
      .set('pageSize', pageSize.toString())
      .set('pageIndex', pageIndex.toString());

    return this.http.get(`${this.apiUrl}?request=documents`, { params });
  }

  getDocument(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}?request=documents/${id}`);
  }

  deleteDocument(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}?request=documents/${id}`);
  }

  searchDocuments(query: string): Observable<any> {
    const params = new HttpParams().set('query', query);
    return this.http.get(`${this.apiUrl}?request=search`, { params });
  }
}
