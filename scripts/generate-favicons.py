#!/usr/bin/env python3
"""Genera los favicons PNG/ICO del mismo diseño que public/favicon.svg."""

import os
from PIL import Image, ImageDraw, ImageFont

INK = (23, 25, 28, 255)      # #17191c
PAPER = (255, 255, 255, 255)  # #ffffff

OUT = os.path.join(os.path.dirname(__file__), "..", "public")
OUT = os.path.abspath(OUT)

CANDIDATES = [
    "/usr/share/fonts/truetype/ibm-plex/IBMPlexSerif-Bold.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf",
]
FONT = next((f for f in CANDIDATES if os.path.exists(f)), None)
if FONT is None:
    raise SystemExit("No encuentro una fuente serif en el sistema.")


def rounded_mark(size: int, radius_ratio: float = 0.22, inset_ratio: float = 0.04) -> Image.Image:
    """Marca con esquinas redondeadas y fondo transparente (favicon)."""
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    inset = round(size * inset_ratio)
    radius = round(size * radius_ratio)
    draw.rounded_rectangle(
        [inset, inset, size - inset - 1, size - inset - 1],
        radius=radius,
        fill=INK,
    )
    _draw_letter(img, size, size * 0.60)
    return img


def square_mark(size: int) -> Image.Image:
    """Marca a sangre, sin transparencia, para apple-touch-icon."""
    img = Image.new("RGBA", (size, size), INK)
    _draw_letter(img, size, size * 0.60)
    return img


def _draw_letter(img: Image.Image, size: int, font_size: float) -> None:
    draw = ImageDraw.Draw(img)
    font = ImageFont.truetype(FONT, round(font_size))
    draw.text((size / 2, size / 2 + size * 0.01), "F", font=font, fill=PAPER, anchor="mm")


# apple-touch-icon (iOS espera 180x180, a sangre)
square_mark(180).convert("RGB").save(os.path.join(OUT, "apple-touch-icon.png"))

# PNG para navegadores que no usan SVG
rounded_mark(192).save(os.path.join(OUT, "favicon-192.png"))
rounded_mark(32).save(os.path.join(OUT, "favicon-32.png"))

# ICO multirresolución
rounded_mark(256).save(
    os.path.join(OUT, "favicon.ico"),
    sizes=[(16, 16), (32, 32), (48, 48), (64, 64)],
)

print("Fuente:", FONT)
print("Generados en", OUT)
