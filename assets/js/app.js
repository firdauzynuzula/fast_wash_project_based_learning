// Fast Wash — Interaksi Frontend Aplikasi

document.addEventListener("DOMContentLoaded", () => {
    // 1. Toggle peran (Pemilik / Karyawan) di Auth: pindahkan pill aktif + isi input hidden.
    const roleButtons = document.querySelectorAll(".js-role");
    const roleInput = document.getElementById("role");

    if (roleButtons.length > 0) {
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
    }

    // 2. Tampilkan/sembunyikan kata sandi — ikon mata berganti sesuai status.
    function initPasswordToggle(inputId, toggleId, eyeId, eyeOffId) {
        const pwd = document.getElementById(inputId);
        const pwdToggle = document.getElementById(toggleId);
        const iconEye = document.getElementById(eyeId);
        const iconEyeOff = document.getElementById(eyeOffId);

        if (pwd && pwdToggle && iconEye && iconEyeOff) {
            pwdToggle.addEventListener("click", () => {
                const show = pwd.type === "password";
                pwd.type = show ? "text" : "password";
                iconEye.classList.toggle("hidden", show);
                iconEyeOff.classList.toggle("hidden", !show);
                pwdToggle.setAttribute("aria-label", show ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
            });
        }
    }

    initPasswordToggle("password", "toggle-password", "icon-eye", "icon-eye-off");
    initPasswordToggle("password_confirm", "toggle-password-confirm", "icon-eye-confirm", "icon-eye-off-confirm");

    // 3. Mobile Sidebar Drawer Toggle
    const btnOpenSidebar = document.getElementById("btn-open-sidebar");
    const btnCloseSidebar = document.getElementById("btn-close-sidebar");
    const sidebarBackdrop = document.getElementById("sidebar-backdrop");
    const appSidebar = document.getElementById("app-sidebar");

    function openSidebar() {
        if (appSidebar) {
            appSidebar.classList.remove("-translate-x-full");
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.remove("hidden");
        }
    }

    function closeSidebar() {
        if (appSidebar) {
            appSidebar.classList.add("-translate-x-full");
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.add("hidden");
        }
    }

    if (btnOpenSidebar) btnOpenSidebar.addEventListener("click", openSidebar);
    if (btnCloseSidebar) btnCloseSidebar.addEventListener("click", closeSidebar);
    if (sidebarBackdrop) sidebarBackdrop.addEventListener("click", closeSidebar);

    // 4. Toast Notifikasi Interaksi Form / Aksi Mockup (data-mock="true")
    const toastDemo = document.getElementById("toast-demo");
    const toastDemoTitle = document.getElementById("toast-demo-title");
    const toastDemoMsg = document.getElementById("toast-demo-message");

    window.showMockToast = function (title, message) {
        if (!toastDemo) return;
        if (title && toastDemoTitle) toastDemoTitle.textContent = title;
        if (message && toastDemoMsg) toastDemoMsg.textContent = message;

        toastDemo.classList.remove("hidden");
        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(() => {
            toastDemo.classList.add("hidden");
        }, 4000);
    };

    document.querySelectorAll('form[data-mock="true"]').forEach((form) => {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            const actionDesc = form.dataset.mockAction || "Aksi formulir";
            window.showMockToast(
                "Mode Pratinjau Tampilan",
                `${actionDesc} disimulasikan. Logika backend belum disambungkan ke database.`
            );
        });
    });
});
