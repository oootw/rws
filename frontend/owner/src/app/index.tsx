import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

import { QueryProvider } from './providers/QueryProvider';
import { ToasterProvider } from './providers/ToasterProvider';
import { AppRouter } from './router/AppRouter';
import './styles/index.css';

const container = document.getElementById('root');
if (!container) {
  throw new Error('Root element #root not found');
}

createRoot(container).render(
  <StrictMode>
    <QueryProvider>
      <BrowserRouter basename="/owner">
        <AppRouter />
        <ToasterProvider />
      </BrowserRouter>
    </QueryProvider>
  </StrictMode>,
);
