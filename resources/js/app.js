import "./bootstrap";
import Clipboard from "@ryangjchandler/alpine-clipboard";

Alpine.plugin(Clipboard);
document.documentElement.classList.remove("dark"); // Menghapus kelas dark jika ada
// To use Html5QrcodeScanner (more info below)
import { Html5QrcodeScanner } from "html5-qrcode";

// To use Html5Qrcode (more info below)
import { Html5Qrcode } from "html5-qrcode";
