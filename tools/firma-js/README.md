# firma-js — componente de firma en el navegador

Fuente del bundle `poc/assets/firma.bundle.js` (luego `assets/js/firma.bundle.js`).

- `src/firma.js`   — abre el .p12 (node-forge) y genera la firma CMS/PKCS#7 detached SHA-256.
- `src/sign_cli.js` — el mismo modulo por linea de comandos, para pruebas automatizadas.
- `src/gen_certs.js` — genera los certificados .p12 de PRUEBA (juguete).

## Recompilar (solo en desarrollo; el servidor NO necesita Node)

    cd tools/firma-js
    npm install
    npm run build
