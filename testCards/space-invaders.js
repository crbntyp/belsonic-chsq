// Space Invaders Game
class SpaceInvaders {
    constructor() {
        this.canvas = document.getElementById('gameCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.startButton = document.getElementById('startButton');
        this.restartButton = document.getElementById('restartButton');
        this.scoreDisplay = document.getElementById('score');
        this.livesDisplay = document.getElementById('lives');

        // Game state
        this.isRunning = false;
        this.isPaused = false;
        this.score = 0;
        this.lives = 3;
        this.level = 1;

        // Player
        this.player = {
            x: this.canvas.width / 2 - 25,
            y: this.canvas.height - 60,
            width: 50,
            height: 40,
            speed: 5,
            dx: 0
        };

        // Bullets
        this.bullets = [];
        this.bulletSpeed = 7;

        // Aliens
        this.aliens = [];
        this.alienRows = 4;
        this.alienCols = 8;
        this.alienWidth = 40;
        this.alienHeight = 30;
        this.alienSpeed = 1;
        this.alienDirection = 1;
        this.alienDropDistance = 20;

        // Enemy bullets
        this.enemyBullets = [];
        this.enemyBulletSpeed = 3;
        this.enemyShootChance = 0.001;

        // Input
        this.keys = {};

        this.init();
    }

    init() {
        // Bind event listeners
        this.startButton.addEventListener('click', () => this.start());
        this.restartButton.addEventListener('click', () => this.restart());

        // Keyboard controls
        document.addEventListener('keydown', (e) => {
            if (e.key === ' ' && this.isRunning) {
                e.preventDefault();
                this.shoot();
            }
            this.keys[e.key] = true;
        });

        document.addEventListener('keyup', (e) => {
            this.keys[e.key] = false;
        });

        // Draw initial screen
        this.drawWelcomeScreen();
    }

    start() {
        this.isRunning = true;
        this.startButton.style.display = 'none';
        this.restartButton.style.display = 'none';
        this.createAliens();
        this.gameLoop();
    }

    restart() {
        this.score = 0;
        this.lives = 3;
        this.level = 1;
        this.alienSpeed = 1;
        this.bullets = [];
        this.enemyBullets = [];
        this.updateUI();
        this.start();
    }

    createAliens() {
        this.aliens = [];
        const startX = 60;
        const startY = 60;
        const spacingX = 60;
        const spacingY = 50;

        for (let row = 0; row < this.alienRows; row++) {
            for (let col = 0; col < this.alienCols; col++) {
                this.aliens.push({
                    x: startX + col * spacingX,
                    y: startY + row * spacingY,
                    width: this.alienWidth,
                    height: this.alienHeight,
                    active: true,
                    type: row < 2 ? 'squid' : 'crab'
                });
            }
        }
    }

    shoot() {
        this.bullets.push({
            x: this.player.x + this.player.width / 2 - 2,
            y: this.player.y,
            width: 4,
            height: 15,
            speed: this.bulletSpeed
        });
    }

    enemyShoot(alien) {
        this.enemyBullets.push({
            x: alien.x + alien.width / 2 - 2,
            y: alien.y + alien.height,
            width: 4,
            height: 15,
            speed: this.enemyBulletSpeed
        });
    }

    update() {
        if (!this.isRunning) return;

        // Move player
        if (this.keys['ArrowLeft']) {
            this.player.dx = -this.player.speed;
        } else if (this.keys['ArrowRight']) {
            this.player.dx = this.player.speed;
        } else {
            this.player.dx = 0;
        }

        this.player.x += this.player.dx;

        // Keep player in bounds
        if (this.player.x < 0) this.player.x = 0;
        if (this.player.x + this.player.width > this.canvas.width) {
            this.player.x = this.canvas.width - this.player.width;
        }

        // Update bullets
        this.bullets = this.bullets.filter(bullet => {
            bullet.y -= bullet.speed;
            return bullet.y > 0;
        });

        // Update enemy bullets
        this.enemyBullets = this.enemyBullets.filter(bullet => {
            bullet.y += bullet.speed;
            return bullet.y < this.canvas.height;
        });

        // Move aliens
        let changeDirection = false;
        this.aliens.forEach(alien => {
            if (!alien.active) return;

            alien.x += this.alienSpeed * this.alienDirection;

            if (alien.x <= 0 || alien.x + alien.width >= this.canvas.width) {
                changeDirection = true;
            }

            // Random enemy shooting
            if (Math.random() < this.enemyShootChance) {
                this.enemyShoot(alien);
            }
        });

        if (changeDirection) {
            this.alienDirection *= -1;
            this.aliens.forEach(alien => {
                alien.y += this.alienDropDistance;
            });
        }

        // Check collisions
        this.checkCollisions();

        // Check win condition
        if (this.aliens.filter(a => a.active).length === 0) {
            this.levelUp();
        }

        // Check game over
        const alienReachedBottom = this.aliens.some(alien =>
            alien.active && alien.y + alien.height >= this.player.y
        );

        if (alienReachedBottom || this.lives <= 0) {
            this.gameOver();
        }
    }

    checkCollisions() {
        // Player bullets hit aliens
        this.bullets.forEach((bullet, bulletIndex) => {
            this.aliens.forEach(alien => {
                if (!alien.active) return;

                if (this.isColliding(bullet, alien)) {
                    alien.active = false;
                    this.bullets.splice(bulletIndex, 1);
                    this.score += alien.type === 'squid' ? 30 : 20;
                    this.updateUI();
                }
            });
        });

        // Enemy bullets hit player
        this.enemyBullets.forEach((bullet, bulletIndex) => {
            if (this.isColliding(bullet, this.player)) {
                this.enemyBullets.splice(bulletIndex, 1);
                this.lives--;
                this.updateUI();

                if (this.lives > 0) {
                    // Flash effect
                    this.flashPlayer();
                }
            }
        });
    }

    isColliding(rect1, rect2) {
        return rect1.x < rect2.x + rect2.width &&
               rect1.x + rect1.width > rect2.x &&
               rect1.y < rect2.y + rect2.height &&
               rect1.y + rect1.height > rect2.y;
    }

    flashPlayer() {
        let flashes = 0;
        const flashInterval = setInterval(() => {
            this.player.visible = !this.player.visible;
            flashes++;
            if (flashes > 6) {
                this.player.visible = true;
                clearInterval(flashInterval);
            }
        }, 100);
    }

    levelUp() {
        this.level++;
        this.alienSpeed += 0.5;
        this.enemyShootChance += 0.0005;
        this.createAliens();

        // Show level up message
        this.isPaused = true;
        setTimeout(() => {
            this.isPaused = false;
        }, 2000);
    }

    gameOver() {
        this.isRunning = false;
        this.restartButton.style.display = 'inline-block';
    }

    draw() {
        // Clear canvas
        this.ctx.fillStyle = '#000000';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

        if (!this.isRunning && this.score === 0) {
            this.drawWelcomeScreen();
            return;
        }

        // Draw player
        if (this.player.visible !== false) {
            this.drawPlayer();
        }

        // Draw bullets
        this.ctx.fillStyle = '#00ff00';
        this.bullets.forEach(bullet => {
            this.ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        });

        // Draw enemy bullets
        this.ctx.fillStyle = '#ff0000';
        this.enemyBullets.forEach(bullet => {
            this.ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
        });

        // Draw aliens
        this.aliens.forEach(alien => {
            if (alien.active) {
                this.drawAlien(alien);
            }
        });

        // Draw game over / level up messages
        if (!this.isRunning) {
            this.drawGameOver();
        } else if (this.isPaused) {
            this.drawLevelUp();
        }
    }

    drawPlayer() {
        this.ctx.fillStyle = '#00ff00';
        // Simple tank shape
        this.ctx.fillRect(this.player.x + 15, this.player.y, 20, 10);
        this.ctx.fillRect(this.player.x + 5, this.player.y + 10, 40, 20);
        this.ctx.fillRect(this.player.x, this.player.y + 30, 50, 10);
    }

    drawAlien(alien) {
        if (alien.type === 'squid') {
            this.ctx.fillStyle = '#ff00ff';
        } else {
            this.ctx.fillStyle = '#00ffff';
        }

        // Simple alien shape
        const centerX = alien.x + alien.width / 2;
        const centerY = alien.y + alien.height / 2;

        // Body
        this.ctx.fillRect(alien.x + 10, alien.y + 5, alien.width - 20, alien.height - 10);
        // Eyes
        this.ctx.fillStyle = '#ffffff';
        this.ctx.fillRect(alien.x + 12, alien.y + 8, 8, 8);
        this.ctx.fillRect(alien.x + alien.width - 20, alien.y + 8, 8, 8);
        // Legs
        this.ctx.fillStyle = alien.type === 'squid' ? '#ff00ff' : '#00ffff';
        this.ctx.fillRect(alien.x + 5, alien.y + alien.height - 5, 8, 5);
        this.ctx.fillRect(alien.x + alien.width - 13, alien.y + alien.height - 5, 8, 5);
    }

    drawWelcomeScreen() {
        this.ctx.fillStyle = '#000000';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

        this.ctx.fillStyle = '#00ff00';
        this.ctx.font = 'bold 48px monospace';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('SPACE INVADERS', this.canvas.width / 2, this.canvas.height / 2 - 50);

        this.ctx.font = '20px monospace';
        this.ctx.fillStyle = '#ffffff';
        this.ctx.fillText('Press START GAME to begin', this.canvas.width / 2, this.canvas.height / 2 + 20);
    }

    drawGameOver() {
        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

        this.ctx.fillStyle = '#ff0000';
        this.ctx.font = 'bold 60px monospace';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('GAME OVER', this.canvas.width / 2, this.canvas.height / 2 - 30);

        this.ctx.fillStyle = '#ffffff';
        this.ctx.font = '24px monospace';
        this.ctx.fillText(`Final Score: ${this.score}`, this.canvas.width / 2, this.canvas.height / 2 + 30);
    }

    drawLevelUp() {
        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

        this.ctx.fillStyle = '#00ff00';
        this.ctx.font = 'bold 48px monospace';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(`LEVEL ${this.level}`, this.canvas.width / 2, this.canvas.height / 2);
    }

    updateUI() {
        this.scoreDisplay.textContent = this.score;
        this.livesDisplay.textContent = this.lives;
    }

    gameLoop() {
        if (!this.isPaused) {
            this.update();
        }
        this.draw();

        if (this.isRunning) {
            requestAnimationFrame(() => this.gameLoop());
        }
    }
}

// Initialize game when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const game = new SpaceInvaders();
});
