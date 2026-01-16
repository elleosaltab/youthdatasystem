    document.addEventListener("DOMContentLoaded", () => {
      const phoneInput = document.querySelector("input[name='phone']");

if (phoneInput) {
  phoneInput.addEventListener("input", () => {
    phoneInput.value = phoneInput.value.replace(/[^0-9]/g, "");
    if (phoneInput.value.length > 11) {
      phoneInput.value = phoneInput.value.slice(0, 11);
    }
  });
}


    const form = document.getElementById("registrationForm");
    const municipality = document.getElementById("municipality");
    const barangay = document.getElementById("barangay");
    const fileInput = document.getElementById("supporting_doc");
    const fileName = document.getElementById("fileName");
    const notif = document.getElementById("notification");

    const barangays = {
      "Bagamanoc": ["Antipolo","Bacak","Bagatabao","Bugao","Cahan","Hinipaan","Magsaysay","Poblacion","Quigaray","Quezon (Pancayanan)","Sagrada","Salvacion (Panuto)","San Isidro","San Rafael (Mahantod)","San Vicente","Santa Mesa","Santa Teresa","Suchan"],
      "Baras": ["Agban","Abihao","Eastern (Poblacion)","Western (Poblacion)","Puraran","Putsan","Benticayan","Batolinao","Bagong Sirang","Quezon","Rizal","Sagrada","Rural","Salvacion","San Lorenzo","San Miguel","Santa Maria","Tilod","Ginitligan","Paniquihan","Macutal","Moning","Nagbarorong","P. Teston","Danao","Caragumihan","Guinsaanan"],
      "Bato": ["Aroyao Pequeño","Bagumbayan","Banawang","Batalay","Binanuahan","Bote","Buenavista","Cabugao","Cagraray","Carorian","Guinobatan","Ilawod","Libjo","Libod (Poblacion)","Marinawa","Mintay","Oguis","Pananaogan","San Andres","San Pedro","San Roque","Sta. Isabel","Sibacungan","Sipi","Talisay","Tamburan","Tilis"],
      "Caramoran": ["Baybay (Pob.)","Bocon","Bothoan (Pob.)","Buenavista","Bulalacao","Camburo","Dariao","Datag East","Datag West","Guiamlong","Hitoma","Icanbato (Pob.)","Inalmasinan","Iyao","Mabini","Maui","Maysuran","Milaviga","Panique","Sabangan","Sabloyon","Salvacion","Supang","Toytoy (Pob.)","Tubli","Tucao","Obi"],
      "Gigmoto": ["Biong","Dororian","Poblacion District I","Poblacion District II","Poblacion District III","San Pedro","San Vicente","Sicmil","Sioron"],
      "Pandan": ["Bagawang","Balagñonan","Baldoc","Canlubi","Catamban","Cobo","Hiyop","Lourdes","Lumabao","Libod","Marambong","Napo","Pandan del Norte","Pandan del Sur","Oga","Panuto","Porot (San Jose)","Salvacion (Tariwara)","San Andres (Dinungsuran)","San Isidro (Langob)","San Rafael (Bogtong)","San Roque","Santa Cruz (Catagbacan)","Tabugoc","Tokio","Wagdas"],
      "Panganiban": ["Alinawan","Babaguan","Bagong Bayan","Burabod","Cabuyoan","Cagdarao","Mabini","Maculiw","Panay","Tapon (Pangcayanan)","Salvacion","San Joaquin","San Jose","San Juan","San Miguel","San Nicolas","San Vicente","Santa Ana","Santa Maria","Santo Tomas","Santo Niño","Santo Cristo","Santo Niño"],
      "San Andres": ["Agojo","Alibuag","Asgad (Juan M. Alberto)","Bagong Sirang","Barihay","Batong Paloway","Belmonte (Poblacion)","Bislig","Bon-ot","Cabungahan","Cabcab","Carangag","Catagbacan","Codon","Comagaycay","Datag","Divino Rostro (Poblacion)","Esperanza (Poblacion)","Hilawan","Lictin","Lubas","Manambrag","Mayngaway","Palawig","Puting Baybay","Rizal","Salvacion (Poblacion)","San Isidro","San Jose","San Roque (Poblacion)","San Vicente","Santa Cruz (Poblacion)","Sapang Palay (Poblacion)","Tibang","Timbaan","Tominawog","Wagdas (Poblacion)","Yocti"],
      "San Miguel": ["Atsan (District I)","Balatohan","Boton","Buhi","Dayawa","J. M. Alberto","Katipunan","Kilikilihan","Mabato","Obo","Pacogon","Pagsangahan","Pangilao","Paraiso","Poblacion District II","Poblacion District III","Progreso","Salvacion (Patagan)","San Juan (Aroyao)","San Marcos","Santa Elena (Patagan)","Siay","Solong","Tobrehon"],
      "Viga": ["Ananong","Asuncion","Batohonan","Begonia","Botinagan","Buenavista","Mabini","Magsaysay","Ogbong","Peñafrancia","Quirino","San Isidro (Poblacion)","San Jose (Poblacion)","San Pedro (Poblacion)","Santa Rosa","Soboc","Tambongon","Tinago","Villa Aurora","San Roque (Poblacion)","San Vicente (Poblacion)"],
      "Virac": ["Antipolo del Norte","Antipolo del Sur","Balite","Batag","Bigaa","Buenavista","Buyo","Cabihian","Calabnigan","Calampong","Calatagan Proper","Cawit","Calatagan Tibang","Capilihan","Casoocan","Cavinitan","Concepcion (Poblacion)","Constantino (Poblacion)","Danicop","Dugui Wala","Dugui Too","F. Tacorda Village","Francia (Poblacion)","Gogon Centro","Gogon Sirangan","Hawan Grande","Hawan Ilaya","Hicming","Igang","GMA Poniton","Lanao (Poblacion)","Mislagan","Magnesia del Norte","Magnesia del Sur","Marcelo Alberto (Poblacion)","Marilima","Pajo Baguio","Pajo San Isidro","Palnab del Norte","Palnab del Sur","Palta Salvacion","Palta Small","Rawis (Poblacion)","Salvacion","San Isidro Village","San Jose (Poblacion)","San Juan (Poblacion)","San Pablo (Poblacion)","San Pedro (Poblacion)","San Roque (Poblacion)","San Vicente","Ibong Sapa (San Vicente Sur)","Santa Cruz (Poblacion)","Santa Elena (Poblacion)","Sto. Cristo","Sto. Domingo","Sto. Niño","Simamla","Sogod Bliss","Sogod-Simamla", "Talisoy","Sogod-Tibgao","Tubaon","Valencia"]
    };

    municipality.addEventListener("change", () => {
      barangay.innerHTML = "<option value=''>Select Barangay</option>";
      const list = barangays[municipality.value] || [];
      list.forEach(b => {
        const opt = document.createElement("option");
        opt.value = b;
        opt.textContent = b;
        barangay.appendChild(opt);
      });
    });

    if (fileInput) {
      fileInput.addEventListener("change", () => {
        fileName.textContent = fileInput.files.length ? fileInput.files[0].name : "No file chosen";
      });
    }

    window.togglePassword = function(id, btn) {
      const field = document.getElementById(id);
      if (field.type === "password") {
        field.type = "text";
        btn.textContent = "Hide";
      } else {
        field.type = "password";
        btn.textContent = "Show";
      }
    };

    form.addEventListener("submit", (e) => {
      const pass = document.getElementById("password").value;
      const confirm = document.getElementById("confirm").value;

      if (!document.getElementById("terms").checked) {
        e.preventDefault();
        return showNotif("⚠️ You must agree to the Terms and Conditions.", true);
      }

      if (pass !== confirm) {
        e.preventDefault();
        return showNotif("❌ Passwords do not match!", true);
      }

      const strong = /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/;
      if (!strong.test(pass)) {
        e.preventDefault();
        return showNotif("❌ Weak password. Use 8+ chars with upper, lower, number, special.", true);
      }
    });

    function showNotif(msg, error = false) {
  if (!notif) {
    alert(msg); 
    return;
  }

  notif.textContent = msg;
  notif.style.background = error ? "#e74c3c" : "#2ecc71";
  notif.style.color = "#fff";
  notif.style.display = "block";

  setTimeout(() => notif.style.display = "none", 3500);
}

  });


const toggleBtn = document.getElementById("themeToggle");
const themeLink = document.getElementById("themeStylesheet");

let theme = localStorage.getItem("kk-theme") || "dark";

function applyTheme() {
  if (theme === "light") {
    themeLink.href = "../assets/css/reg.light.css";
    toggleBtn.textContent = "☀️";
  } else {
    themeLink.href = "../assets/css/reg.css";
    toggleBtn.textContent = "🌙";
  }
}

toggleBtn.addEventListener("click", () => {
  theme = theme === "dark" ? "light" : "dark";
  localStorage.setItem("kk-theme", theme);
  applyTheme();
});

applyTheme();
