// SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
// SPDX-License-Identifier: AGPL-3.0-or-later
import { createApp } from 'vue'
import App from './App.vue'
import './style.css'
const root = document.getElementById('librefinance-nfse-app')
if (root) createApp(App, { spaceId: Number(root.dataset.spaceId), embedded: root.dataset.embed==='1' }).mount(root)
