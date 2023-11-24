import fs from "fs";
import path from "path";

let packageFile = new URL("../../../package.json", import.meta.url);
let packageLockFile = new URL("../../../package-lock.json", import.meta.url);
const packageStats = fs.statSync(packageFile);

if (!packageStats) {
    console.err(`${packageFile} not found`);
    process.exit(1);
}
const packageLockStats = fs.statSync(packageLockFile);
if (!packageLockStats) {
    console.err(`${packageLockFile} not found`);
    process.exit(1);
}
if (packageLockStats.mtimeMs < packageStats.mtimeMs) {
    console.error("Warning:  Your package-lock.json file is older than package.json.  You probably need to run npm install!");
    process.exit(1);
} else {
    console.log(packageLockStats.mtimeMs, packageStats.mtimeMs);
}
