function buscarDeudaConfirmado(cuit) {
  const mensajeDiv = document.getElementById('mensaje');

  mensajeDiv.innerHTML = `<div style="background: #e0e0e0; color: #333; padding: 15px; font-weight: bold; text-align: center; border-radius: 5px;">
    🔄 Consultando BCRA, por favor espere...
  </div>`;

  fetch(`consulta.php?cuit=${encodeURIComponent(cuit)}`)
    .then(async res => {
      let data;
      try {
        data = await res.json();
      } catch (error) {
        throw new Error('Respuesta no válida del servidor');
      }

      if (!res.ok) {
        throw new Error(data.message || 'Error en la consulta');
      }

      return data; 
    })
    .then(data => {
      mensajeDiv.innerHTML = '';

      if (data.status === 'deuda') {
        mensajeDiv.innerHTML = `<div style="background: #ffcccc; color: #cc0000; padding: 15px; font-weight: bold; text-align: center; border-radius: 5px;">
          🔴 El CUIT tiene deudas registradas. Pronto nos comunicaremos con Usted.
        </div>`;
      } else if (data.status === 'sindeuda') {
        mensajeDiv.innerHTML = `<div style="background: #ccffcc; color: #006600; padding: 15px; font-weight: bold; text-align: center; border-radius: 5px;">
          🟢 El CUIT no registra deudas. Pronto nos comunicaremos con Usted.
        </div>`;
      } else {
        mensajeDiv.innerHTML = `<div style="background: #ffe0b3; color: #cc6600; padding: 15px; font-weight: bold; text-align: center; border-radius: 5px;">
          ⚠️ Hubo un problema al procesar la consulta.
        </div>`;
      }
    })
    .catch(err => {
      console.error('Error:', err);
      mensajeDiv.innerHTML = `<div style="background: #ffcccc; color: #cc0000; padding: 15px; font-weight: bold; text-align: center; border-radius: 5px;">
        ⚠️ ${err.message}
      </div>`;
    });
}


function buscarDeuda() {
  const cuit = document.getElementById('cuit').value.trim();
  const mensajeDiv = document.getElementById('mensaje');

  if (cuit.length !== 11 || isNaN(cuit)) {
    mensajeDiv.innerHTML = `<div style="background: #ffcccc; color: #cc0000; padding: 15px; font-weight: bold; text-align: center; border-radius: 5px;">
      ⚠️ CUIT/CUIL inválido. Debe tener 11 dígitos.
    </div>`;
    return;
  }

  const modal = document.getElementById('confirmModal');
  const confirmText = document.getElementById('confirmText');
  const confirmBtn = document.getElementById('confirmBtn');
  const cancelBtn = document.getElementById('cancelBtn');

  confirmText.innerText = `¿Estás seguro que querés buscar las deudas del CUIL/CUIT: ${cuit}?`;
  modal.style.display = 'flex';

  confirmBtn.onclick = function() {
    modal.style.display = 'none';
    buscarDeudaConfirmado(cuit);
  };

  cancelBtn.onclick = function() {
    modal.style.display = 'none';
  };
}
document.addEventListener("DOMContentLoaded", () => {
  const hamburger = document.getElementById("hamburger");
  const drawer = document.getElementById("drawer");
  const closeDrawer = document.getElementById("closeDrawer");

  hamburger.addEventListener("click", () => {
    drawer.classList.toggle("open");
  });

  closeDrawer.addEventListener("click", () => {
    drawer.classList.remove("open");
  });
});




