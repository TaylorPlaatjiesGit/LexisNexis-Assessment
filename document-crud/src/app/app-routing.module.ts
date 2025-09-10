import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { DocumentListComponent } from './components/document-list/document-list.component';
import { DocumentUploadComponent } from './components/document-upload/document-upload.component';
import { DocumentDetailComponent } from './components/document-detail/document-detail.component';
import { SearchComponent } from './components/search/search.component';

export const routes: Routes = [
  { path: '', component: DocumentListComponent },
  { path: 'upload', component: DocumentUploadComponent },
  { path: 'documents/:id', component: DocumentDetailComponent },
  { path: 'search', component: SearchComponent }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule {}
