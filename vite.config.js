// SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
// SPDX-License-Identifier: AGPL-3.0-or-later
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'node:path'
export default defineConfig({
  plugins:[vue()],
  // Browser bundles cannot use Node process.env in Vite library/IIFE mode.
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env.NODE_DEBUG': JSON.stringify(''),
  },
  build:{outDir:'js',emptyOutDir:true,cssCodeSplit:false,
    lib:{entry:resolve('src/main.js'),name:'LibreFinanceNfse',formats:['iife'],fileName:()=> 'main.js',cssFileName:'style'}},
})
