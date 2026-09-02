from pathlib import Path
from textwrap import wrap

from PIL import Image, ImageDraw, ImageFont


OUT = Path(__file__).resolve().parents[1] / "docs" / "evidence"
FONT = "/System/Library/Fonts/Supplemental/Arial.ttf"
BOLD = "/System/Library/Fonts/Supplemental/Arial Bold.ttf"

INK = "#16302B"
EMERALD = "#2F6B55"
EMERALD_LIGHT = "#E8F3ED"
BLUE = "#315B73"
BLUE_LIGHT = "#EAF1F5"
MUTED = "#52635E"
LINE = "#6F817B"
WHITE = "#FFFFFF"
BG = "#F8FAF9"


def font(size: int, bold: bool = False):
    return ImageFont.truetype(BOLD if bold else FONT, size)


def text(draw, xy, value, size=24, fill=INK, bold=False, anchor=None):
    draw.text(xy, value, font=font(size, bold), fill=fill, anchor=anchor)


def wrapped_lines(value: str, width: int):
    return wrap(value, width=width, break_long_words=False, break_on_hyphens=False)


def class_box(draw, box, title, stereotype=None, attributes=None, operations=None, accent=EMERALD):
    x, y, w, h = box
    draw.rounded_rectangle((x, y, x + w, y + h), radius=18, fill=WHITE, outline=accent, width=4)
    draw.rounded_rectangle((x, y, x + w, y + 78), radius=18, fill=accent)
    draw.rectangle((x, y + 54, x + w, y + 78), fill=accent)
    if stereotype:
        text(draw, (x + w / 2, y + 18), stereotype, size=18, fill="#DFF3E9", anchor="mm")
        text(draw, (x + w / 2, y + 52), title, size=27, fill=WHITE, bold=True, anchor="mm")
    else:
        text(draw, (x + w / 2, y + 41), title, size=28, fill=WHITE, bold=True, anchor="mm")

    cursor = y + 98
    if attributes:
        for item in attributes:
            for line in wrapped_lines(item, max(22, int(w / 16))):
                text(draw, (x + 22, cursor), line, size=20, fill=INK)
                cursor += 27
        if operations:
            draw.line((x, cursor + 6, x + w, cursor + 6), fill="#CAD7D1", width=2)
            cursor += 24
    if operations:
        for item in operations:
            for line in wrapped_lines(item, max(22, int(w / 16))):
                text(draw, (x + 22, cursor), line, size=20, fill=BLUE)
                cursor += 27


def arrow_head(draw, end, start, fill=LINE, size=16, open_head=False):
    import math

    ex, ey = end
    sx, sy = start
    angle = math.atan2(ey - sy, ex - sx)
    left = (ex - size * math.cos(angle - 0.55), ey - size * math.sin(angle - 0.55))
    right = (ex - size * math.cos(angle + 0.55), ey - size * math.sin(angle + 0.55))
    if open_head:
        draw.polygon([end, left, right], fill=WHITE, outline=fill)
        draw.line([end, left, right, end], fill=fill, width=3)
    else:
        draw.polygon([end, left, right], fill=fill)


def line(draw, points, label=None, dashed=False, arrow=False, open_head=False, label_xy=None, fill=LINE):
    if dashed:
        for a, b in zip(points[:-1], points[1:]):
            x1, y1 = a
            x2, y2 = b
            steps = max(1, int(((x2 - x1) ** 2 + (y2 - y1) ** 2) ** 0.5 / 22))
            for i in range(0, steps, 2):
                p1 = (x1 + (x2 - x1) * i / steps, y1 + (y2 - y1) * i / steps)
                p2 = (x1 + (x2 - x1) * min(i + 1, steps) / steps, y1 + (y2 - y1) * min(i + 1, steps) / steps)
                draw.line([p1, p2], fill=fill, width=4)
    else:
        draw.line(points, fill=fill, width=4, joint="curve")
    if arrow:
        arrow_head(draw, points[-1], points[-2], fill=fill, open_head=open_head)
    if label:
        lx, ly = label_xy or points[len(points) // 2]
        bbox = draw.textbbox((0, 0), label, font=font(18, True))
        pad = 7
        draw.rounded_rectangle((lx - pad, ly - pad, lx + bbox[2] + pad, ly + bbox[3] + pad), radius=8, fill=BG)
        text(draw, (lx, ly), label, size=18, fill=MUTED, bold=True)


def title_block(draw, title, subtitle, width=1800):
    text(draw, (80, 44), title, size=38, fill=INK, bold=True)
    text(draw, (80, 94), subtitle, size=21, fill=MUTED)
    draw.line((80, 132, width - 80, 132), fill="#BCD0C7", width=3)


def entity_diagram():
    image = Image.new("RGB", (1800, 1200), BG)
    draw = ImageDraw.Draw(image)
    title_block(draw, "FoodBridge Module 3 - Entity Class Diagram", "Object references and multiplicities implemented with Eloquent relationships")

    user = (700, 170, 400, 190)
    donation = (80, 470, 500, 270)
    request = (680, 470, 540, 300)
    match = (280, 900, 460, 220)
    notification = (1240, 900, 500, 220)

    # Draw associations first so no connector can cross readable class content.
    line(draw, [(780, 360), (520, 430), (450, 470)], "1 donor / 0..* donations", arrow=True, label_xy=(370, 405))
    line(draw, [(900, 360), (900, 470)], "1 recipient / 0..* requests", arrow=True, label_xy=(925, 400))
    line(draw, [(1040, 350), (1490, 500), (1490, 900)], "1 user / 0..* notifications", arrow=True, label_xy=(1330, 520))
    line(draw, [(420, 740), (500, 900)], "1 donation / 0..* matches", arrow=True, label_xy=(225, 815))
    line(draw, [(840, 770), (700, 900)], "1 request / 0..* matches", arrow=True, label_xy=(700, 820))
    line(draw, [(1220, 690), (1420, 900)], "1 request / 0..* notifications", arrow=True, label_xy=(1190, 800))
    line(draw, [(680, 610), (580, 610)], dashed=True, arrow=True, fill=BLUE)

    class_box(draw, user, "User", "entity", ["name: string", "email: string", "role: string"])
    class_box(draw, donation, "Donation", "entity", ["foodName: string", "category: string", "quantityAvailable: int", "expiresAt: datetime", "donor: User"])
    class_box(draw, request, "FoodRequest", "entity", ["category: string", "quantityRequested: int", "quantityMatched: int", "status: string", "recipient: User", "preferredDonation: Donation?"])
    class_box(draw, match, "MatchRecord", "entity", ["quantityAllocated: int", "status: string", "foodRequest: FoodRequest", "donation: Donation"], accent=BLUE)
    class_box(draw, notification, "MatchNotification", "entity", ["type: string", "message: string", "user: User", "foodRequest: FoodRequest"], accent=BLUE)

    image.save(OUT / "entity-class-diagram.png", quality=95)


def observer_diagram():
    image = Image.new("RGB", (1900, 1200), BG)
    draw = ImageDraw.Draw(image)
    title_block(draw, "FoodBridge Module 3 - Observer Pattern", "Matching publishes an outcome; independent observers react without changing allocation logic", width=1900)

    service = (60, 190, 430, 220)
    publisher = (610, 180, 500, 280)
    observer = (610, 560, 500, 220)
    donor = (60, 900, 500, 210)
    recipient = (610, 900, 500, 210)
    outcome = (1300, 180, 520, 240)
    succeeded = (1190, 610, 220, 170)
    partial = (1460, 610, 220, 170)
    failed = (1700, 610, 180, 170)

    # Connectors sit underneath class boxes to preserve the UML compartments.
    line(draw, [(490, 300), (610, 300)], "publishes", arrow=True, label_xy=(515, 255), fill=BLUE)
    line(draw, [(860, 460), (860, 560)], "notifies", arrow=True, label_xy=(880, 490))
    line(draw, [(310, 900), (310, 840), (760, 840), (760, 780)], "implements", dashed=True, arrow=True, open_head=True, label_xy=(435, 800))
    line(draw, [(860, 900), (860, 780)], "implements", dashed=True, arrow=True, open_head=True, label_xy=(885, 820))
    line(draw, [(1300, 610), (1430, 520), (1430, 420)], None, arrow=True, open_head=True, fill=BLUE)
    line(draw, [(1570, 610), (1570, 420)], None, arrow=True, open_head=True, fill=BLUE)
    line(draw, [(1790, 610), (1740, 520), (1740, 420)], None, arrow=True, open_head=True, fill=BLUE)

    class_box(draw, service, "AutoMatchingService", "subject client", [], ["+ match(FoodRequest): FoodRequest"], accent=BLUE)
    class_box(draw, publisher, "MatchPublisher", "subject", ["- observers: MatchObserver[]"], ["+ attach(MatchObserver)", "+ detach(MatchObserver)", "+ notify(MatchOutcome)"])
    class_box(draw, observer, "MatchObserver", "interface", [], ["+ update(MatchOutcome): void"])
    class_box(draw, donor, "DonorNotifier", "concrete observer", [], ["+ update(MatchOutcome): void"])
    class_box(draw, recipient, "RecipientNotifier", "concrete observer", [], ["+ update(MatchOutcome): void"])
    class_box(draw, outcome, "MatchOutcome", "abstract event", ["+ foodRequest: FoodRequest"], ["+ type(): string"], accent=BLUE)
    class_box(draw, succeeded, "MatchSucceeded", "event", [], ["+ matched"], accent=BLUE)
    class_box(draw, partial, "PartialMatch", "event", [], ["+ partial"], accent=BLUE)
    class_box(draw, failed, "MatchFailed", "event", [], ["+ pending"], accent=BLUE)

    image.save(OUT / "observer-pattern-diagram.png", quality=95)


if __name__ == "__main__":
    OUT.mkdir(parents=True, exist_ok=True)
    entity_diagram()
    observer_diagram()
    print(OUT / "entity-class-diagram.png")
    print(OUT / "observer-pattern-diagram.png")
