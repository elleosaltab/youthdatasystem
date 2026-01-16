document.addEventListener("DOMContentLoaded", function () {

  const municipalitySelect = document.getElementById("municipality");
  const barangaySelect = document.getElementById("barangay");
  const form = document.getElementById("skRegisterForm");
  const toggleBtn = document.getElementById("themeToggle");
  const themeLink = document.getElementById("themeStylesheet");


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
    "Virac": ["Antipolo del Norte","Antipolo del Sur","Balite","Batag","Bigaa","Buenavista","Buyo","Cabihian","Calabnigan","Calampong","Calatagan Proper","Calatagan Tibang","Capilihan","Casoocan","Cavinitan","Concepcion (Poblacion)","Constantino (Poblacion)","Danicop","Dugui Wala","Dugui Too","F. Tacorda Village","Francia (Poblacion)","Gogon Centro","Gogon Sirangan","Hawan Grande","Hawan Ilaya","Hicming","Igang","Poniton","Lanao (Poblacion)","Magnesia del Norte","Magnesia del Sur","Marcelo Alberto (Poblacion)","Marilima","Pajo Baguio","Pajo San Isidro","Palnab del Norte","Palnab del Sur","Palta Big","Palta Salvacion","Palta Small","Rawis (Poblacion)","Salvacion","San Isidro Village","San Jose (Poblacion)","San Juan (Poblacion)","San Pablo (Poblacion)","San Pedro (Poblacion)","San Roque (Poblacion)","San Vicente","Ibong Sapa (San Vicente Sur)","Santa Cruz (Poblacion)","Santa Elena (Poblacion)","Santo Cristo","Santo Domingo","Santo Niño","Simamla","Sogod-Simamla","Talisoy","Sogod-Tibgao","Tubaon","Valencia"]
  };
 if (municipalitySelect && barangaySelect) {
    municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';

    Object.keys(barangays).forEach(m => {
      const opt = document.createElement("option");
      opt.value = m;
      opt.textContent = m;
      municipalitySelect.appendChild(opt);
    });

    municipalitySelect.addEventListener("change", function () {
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      if (barangays[this.value]) {
        barangays[this.value].forEach(b => {
          const opt = document.createElement("option");
          opt.value = b;
          opt.textContent = b;
          barangaySelect.appendChild(opt);
        });
      }
    });
  }

  /* ===== FORM VALIDATION ===== */
  if (form) {
    form.addEventListener("submit", function (e) {
      const email = this.email.value.trim();
      const password = this.password.value;
      const confirm = this.confirm_password.value;
      const pdf = this.proof_pdf.files[0];

      if (!/^\S+@\S+\.\S+$/.test(email)) {
        alert("Please enter a valid email address.");
        e.preventDefault();
        return;
      }

      if (
        password.length < 8 ||
        !/[A-Z]/.test(password) ||
        !/[a-z]/.test(password) ||
        !/[0-9]/.test(password) ||
        !/[\W_]/.test(password)
      ) {
        alert("Password must be strong.");
        e.preventDefault();
        return;
      }

      if (password !== confirm) {
        alert("Passwords do not match.");
        e.preventDefault();
        return;
      }

      if (!pdf || pdf.type !== "application/pdf") {
        alert("Upload PDF only.");
        e.preventDefault();
      }
    });
    
  }

 
  if (toggleBtn && themeLink) {
    let isLight = localStorage.getItem("theme") === "light";

    if (isLight) {
      themeLink.href = "../assets/css/register.light.css";
      toggleBtn.textContent = "☀️";
    }

    toggleBtn.addEventListener("click", () => {
      if (isLight) {
        themeLink.href = "../assets/css/register.css";
        toggleBtn.textContent = "🌙";
        localStorage.setItem("theme", "dark");
      } else {
        themeLink.href = "../assets/css/register.light.css";
        toggleBtn.textContent = "☀️";
        localStorage.setItem("theme", "light");
      }
      isLight = !isLight;
    });
  }

});