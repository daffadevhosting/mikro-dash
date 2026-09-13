// Cek status login

  async function logout() {
    if (!await uiConfirm('Keluar dari sesi admin ini?')) return;
    firebase.auth().signOut().then(function() {
      window.location.href = "/login";  // redirect ke halaman login setelah logout
    }).catch(function(error) {
      alert("Logout gagal: " + error.message);
    });
  }

  // Cek status login pengguna
  firebase.auth().onAuthStateChanged(function(user) {
    if (user) {
      console.log("Login sebagai:", user.email);
    } else {
      window.location.href = "/login"; // redirect ke halaman login jika tidak ada user
    }
  });
