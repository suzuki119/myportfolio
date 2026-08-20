import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import 'destyle.css' // 追加
// import './index.css' コメントアウト
import App from './App.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)