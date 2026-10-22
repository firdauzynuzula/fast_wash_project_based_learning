// Fast Wash — interaksi halaman masuk.

// Toggle peran (Pemilik / Karyawan): pindahkan pill aktif + isi input hidden.
const roleButtons = document.querySelectorAll(".js-role");
const roleInput = document.getElementById("role");

roleButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
        roleButtons.forEach((b) => {
            const active = b === btn;
            b.classList.toggle("bg-white", active);
            b.classList.toggle("text-[#1668dc]", active);
            b.classList.toggle("shadow-sm", active);
            b.classList.toggle("text-[#16305b]", !active);
            b.setAttribute("aria-pressed", String(active));
        });
        if (roleInput) roleInput.value = btn.dataset.role;
    });
});

// Tampilkan/sembunyikan kata sandi — ikon mata berganti sesuai status.
const pwd = document.getElementById("password");
const pwdToggle = document.getElementById("toggle-password");
const iconEye = document.getElementById("icon-eye");
const iconEyeOff = document.getElementById("icon-eye-off");

if (pwd && pwdToggle && iconEye && iconEyeOff) {
    pwdToggle.addEventListener("click", () => {
        const show = pwd.type === "password";
        pwd.type = show ? "text" : "password";
        iconEye.classList.toggle("hidden", show);
        iconEyeOff.classList.toggle("hidden", !show);
        pwdToggle.setAttribute("aria-label", show ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
    });
}
