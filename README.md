# Simplicial Complex Property Check (SCPC)


| branch  | status |
| :------------- | :------------- |
| main | [![tests](https://github.com/martapavelka/scpc/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/martapavelka/scpc/actions/workflows/test.yml) |
| dev | [![tests](https://github.com/martapavelka/scpc/actions/workflows/test.yml/badge.svg?branch=dev)](https://github.com/martapavelka/scpc/actions/workflows/test.yml) |

# Running

1. Online in Google Collab 
    - [dev](https://colab.research.google.com/github/martapavelka/scpc/blob/dev/scpc.ipynb)
    - [main](https://colab.research.google.com/github/martapavelka/scpc/blob/main/scpc.ipynb)

1. Online with user interface
    - [dev](https://www.math.miami.edu/~pavelka/scpc-dev/)
    - [main](https://www.math.miami.edu/~pavelka/scpc/)

3. Locally
    1. Compile and set permissions
    
    ```sh
    jupyter nbconvert --to python scpc.ipynb
    chmod +x scpc.py
    ```
    
    2. Run
    
    ```sh
    echo "1 2 3
    4 5 6
    7 8 9" | ./scpc.py --property under-closed
    ```

# Exit Codes

- `0`: Match
- `1`: Unexpected exception
- `2`: Invalid usage
- `3`: No match
