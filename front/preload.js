const { contextBridge, ipcRenderer } = require('electron');

let backendUrlPromise = new Promise((resolve) => {
  ipcRenderer.once('backend-url', (event, url) => {
    resolve(url);
  });
});

let backendPathPromise = new Promise((resolve) => {
  ipcRenderer.once('backend-path', (event, path) => {
    resolve(path);
  });
});

contextBridge.exposeInMainWorld('electronAPI', {
  getBackendUrl: () => backendUrlPromise,
  getBackendPath: () => backendPathPromise
});


