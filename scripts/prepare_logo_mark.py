from PIL import Image

src = r'c:\Users\lthon\OneDrive\Documentos\DesarrolloWebTercero\proyecto\base-api-mvc\public\logo.png'
out = r'c:\Users\lthon\OneDrive\Documentos\DesarrolloWebTercero\proyecto\base-api-mvc\public\logo-mark.png'

im = Image.open(src).convert('RGBA')
pixels = list(im.getdata())
new = []

for r, g, b, a in pixels:
    # Solo quitar fondo claro; no alterar colores del logo
    if r > 240 and g > 240 and b > 240:
        new.append((255, 255, 255, 0))
    elif r > 225 and g > 225 and b > 225:
        # Suavizar borde del fondo sin tocar el verde
        alpha = int(a * ((255 - min(r, g, b)) / 30))
        new.append((r, g, b, max(0, min(255, alpha))))
    else:
        new.append((r, g, b, a))

im.putdata(new)
im.save(out)
print('saved original colors', out, im.size)
