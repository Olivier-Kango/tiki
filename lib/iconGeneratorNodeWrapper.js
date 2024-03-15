import { execSync } from "child_process";

try {
    const phpPaths = process.platform === "win32" ? execSync("where php").toString().trim() : execSync("which php").toString().trim();
    const php = phpPaths.split("\n")[0];
    execSync(`${php.trim()} console.php build:generateiconlist`, { stdio: "inherit" });
} catch (error) {
    if (error.status === 1) {
        console.warn("PHP executable not found. Please install PHP before proceeding.");
    } else {
        console.error("An error occurred while trying to execute the PHP command:", error.message);
    }
}