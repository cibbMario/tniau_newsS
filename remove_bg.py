from PIL import Image

def remove_black_background(input_path, output_path, threshold=40):
    img = Image.open(input_path).convert("RGBA")
    data = img.getdata()
    
    new_data = []
    for item in data:
        # If the pixel is close to black (RGB < threshold), make it transparent
        if item[0] < threshold and item[1] < threshold and item[2] < threshold:
            new_data.append((0, 0, 0, 0))
        else:
            new_data.append(item)
            
    img.putdata(new_data)
    img.save(output_path, "PNG")
    print("Saved to", output_path)

remove_black_background(r'c:\laragon\www\tniau_newsS\assets\img\logo-tniau-new.jpg', r'c:\laragon\www\tniau_newsS\assets\img\logo-tniau-new.png')
