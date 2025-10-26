const { app, BrowserWindow, dialog, Tray, Menu, nativeImage } = require('electron');
const { exec } = require('child_process');
const path = require('path');
const getPort = require('get-port');

let symfonyProcess = null;
let globalBackendUrl = null;
let tray = null;

const backendPath = app.isPackaged ? path.join(process.resourcesPath, 'app/build/back') : path.join(__dirname, '../back');
const frontendPath = app.isPackaged ? path.join(process.resourcesPath, 'app/public/index.html') : path.join(__dirname, 'public/index.html'); 
const publicPath = app.isPackaged ? path.join(backendPath, 'public') : 'public';

const phpPath = app.isPackaged 
    ? (process.platform === 'win32' ? path.join(process.resourcesPath, 'app/build/php', 'php.exe') : path.join(process.resourcesPath, 'app/build/php', 'php'))
    : (process.platform === 'win32' ? path.join(__dirname, '/build/php/php.exe') : '/usr/local/bin/php') ;

if (process.platform === 'darwin') {
  try {
    fs.chmodSync(phpPath, 0o755); 
  } catch (err) {}
}

app.commandLine.appendSwitch('ignore-certificate-errors');

async function startSymfonyServer() {
  const portRange = [...Array(100).keys()].map(i => 8000 + i);
  try {    

    const port = await getPort({ port: portRange });

    symfonyProcess = exec(`${phpPath} -S localhost:${port} -t ${publicPath}`, { cwd: backendPath }, (error, stdout, stderr) => {
      if (error)  console.error(`[Symfony Error]: ${error.message}`);
      if (stderr) console.error(`[Symfony STDERR]: ${stderr}`);
      if (stdout) console.log(`[Symfony STDOUT]: ${stdout}`);
    });
    globalBackendUrl = `http://localhost:${port}`
    return globalBackendUrl;

  } catch (err) {
    console.error(`❌ Failed to find a free port or start the server: ${err.message}`);
  }
}


async function stopSymfonyServer(backendPath) {
  return new Promise((resolve) => {
    exec('symfony server:stop', { cwd: backendPath }, (error, stdout, stderr) => {
      if (error) {
        console.warn('[Symfony Stop Warning]:', error.message);
      } else {
        console.log('[Symfony] Serveur arrêté avec succès.');
      }
      resolve();
    });
  });
}

async function killSymfonyProcesses() {
  return new Promise((resolve, reject) => {
    // Liste les processus qui écoutent sur les ports 8000-8099
    exec("lsof -i :8000-8099 -sTCP:LISTEN -n -P", (err, stdout, stderr) => {
      if (err) {
        console.warn('Erreur lors de la récupération des processus :', err.message);
        return resolve();
      }

      const pids = new Set();

      stdout.split('\n').forEach(line => {
        const parts = line.trim().split(/\s+/);
        const pid = parts[1];
        const command = parts[0];

        if (command && pid && (command.includes('symfony') || command.includes('php'))) {
          pids.add(pid);
        }
      });

      if (pids.size === 0) {
        console.log("Aucun processus Symfony/php-fpm à tuer.");
        return resolve();
      }

      // Tue les processus trouvés
      const killCmd = `kill -9 ${[...pids].join(' ')}`;
      exec(killCmd, (killErr) => {
        if (killErr) {
          console.warn('Erreur en tuant les processus Symfony/PHP :', killErr.message);
        } else {
          console.log('✅ Processus Symfony/PHP terminés.');
        }
        resolve();
      });
    });
  });
}

function createWindow(backendUrl) {
    const mainWindow = new BrowserWindow({
        width: 1600,
        height: 850,
        webPreferences: {
      contextIsolation: true,
      nodeIntegration: true,
      preload: path.join(__dirname, 'preload.js'), 
      webSecurity: false
        }
    });

  mainWindow.loadFile(frontendPath)

  mainWindow.webContents.on('did-finish-load', () => {
    mainWindow.webContents.send('backend-url', backendUrl);
    mainWindow.webContents.send('backend-path', backendPath);
    });

    // Inspecteur : touche espace en developpement
     if (!app.isPackaged) { 
      mainWindow.webContents.on('before-input-event', (event, input) => {
        if (input.type === 'keyDown' && input.key === ' ') {
          mainWindow.webContents.openDevTools();
        }
      });
    }

    mainWindow.on('close', async (event) => {
        event.preventDefault();

        const choice = await dialog.showMessageBox(mainWindow, {
            type: 'question',
            buttons: ['Annuler', 'Quitter'],
            defaultId: 1,
            cancelId: 0,
            title: 'Confirmation',
            message: 'Êtes-vous sûr de vouloir quitter l\'application ?'
        });

        if (choice.response === 1) {
            mainWindow.removeAllListeners('close');
      await stopSymfonyServer(backendPath);
      await killSymfonyProcesses();
      if(symfonyProcess) symfonyProcess.kill('SIGINT');
            mainWindow.close(); 
        }
    });
  return mainWindow;
}

app.whenReady().then(async() => {
  // symfony serve
  const backendUrl = await startSymfonyServer();
  if (backendUrl) {
    createWindow(backendUrl);
  } else {
    console.error('❌ Impossible de lancer le serveur Symfony.');
  }

  const iconPath = path.join(__dirname, 'public/assets/img/icon.png');
  const icon = nativeImage.createFromPath(iconPath);
  icon.setTemplateImage(true); // !important macos

  // Icon menu
  tray = new Tray(icon);
  const contextMenu = Menu.buildFromTemplate([
    { label: 'Afficher', click: () => { mainWindow.show(); } },
    { label: 'Quitter', click: () => { app.quit(); } }
  ]);
  tray.setToolTip('Cavavin');
  tray.setContextMenu(contextMenu);
});

app.on('window-all-closed', () => {
  app.quit();
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});
